package com.thakivoyage.service;

import static com.thakivoyage.service.InventarioReglas.*;
import static com.thakivoyage.service.PortalQuery.valores;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.time.LocalDate;
import java.util.*;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.BadRequestException;
import jakarta.ws.rs.ClientErrorException;
import jakarta.ws.rs.NotFoundException;

/** Operaciones transaccionales sobre el esquema original de thaki_modificado. */
@ApplicationScoped
public class AlmacenOperacionService {
    @Inject PortalQuery db;
    @Inject UnidadInventarioService unidades;
    @Inject ObjectMapper json;

    private record Movimiento(String codigo, String sufijo, LocalDate fecha, String nota) {}

    public Map<String,Object> formulario() {
        return valores(
            "productos", db.lista("""
                SELECT p.cod_pro,p.des_pro,c.es_serializado,c.es_consumible,
                  (SELECT COUNT(*) FROM inventario_unidad u WHERE u.cod_pro=p.cod_pro)
                FROM producto p JOIN clase_producto c ON c.cod_clase=p.cod_clase
                WHERE p.estado='ACTIVO' AND p.cod_pro NOT LIKE 'DEMO-%' AND p.des_pro NOT LIKE '[DEMO]%'
                ORDER BY p.des_pro
                """,Map.of(),"codigo","nombre","serializado","consumible","unidades"),
            "almacenes", db.lista("SELECT cod_almacen,nombre FROM almacen WHERE estado='ACTIVO' ORDER BY nombre",Map.of(),"codigo","nombre"),
            "personas", db.lista("SELECT cod_persona,CONCAT(nombres,' ',apellidos) FROM persona ORDER BY nombres,apellidos",Map.of(),"codigo","nombre"),
            "unidades", new InventarioConsultaService() {{ db=AlmacenOperacionService.this.db; }}.unidades());
    }

    private Movimiento movimiento(AccesoService.Identidad user, Map<String,Object> datos) {
        String solicitud = texto(datos,"solicitudId",36,true);
        try {
            if (!UUID.fromString(solicitud).toString().equalsIgnoreCase(solicitud)) throw new IllegalArgumentException();
        } catch (IllegalArgumentException error) {
            throw new BadRequestException("Actualiza el formulario antes de registrar la operación.");
        }
        String sufijo = AccesoService.digest(user.id()+"|"+solicitud).substring(0,16).toUpperCase(Locale.ROOT);
        return new Movimiento("MOV-"+sufijo,sufijo,fecha(datos,"fecha",true,false),texto(datos,"observaciones",2000,false));
    }

    private void comprobarRepeticion(Movimiento movimiento) {
        if (!db.lista("SELECT cod_movimiento FROM movimiento_inventario WHERE cod_movimiento=:codigo",Map.of("codigo",movimiento.codigo()),"codigo").isEmpty()) {
            throw new ClientErrorException("Esta operación ya fue registrada. Consulta el historial antes de repetirla.",409);
        }
    }

    private Map<String,Object> producto(String codigo) {
        var producto = db.primero("""
            SELECT p.cod_pro,p.des_pro,p.estado,c.es_serializado,c.es_consumible,
              (SELECT COUNT(*) FROM inventario_unidad u WHERE u.cod_pro=p.cod_pro)
            FROM producto p JOIN clase_producto c ON c.cod_clase=p.cod_clase WHERE p.cod_pro=:codigo FOR UPDATE
            """,Map.of("codigo",codigo),"codigo","nombre","estado","serializado","consumible","unidades");
        if (producto.isEmpty()) throw new NotFoundException("Producto no encontrado.");
        if (!"ACTIVO".equals(producto.get("estado"))) throw new BadRequestException("El producto está inactivo.");
        return producto;
    }

    private boolean individual(Map<String,Object> producto) {
        return marcado(producto.get("serializado"));
    }

    private String almacen(Map<String,Object> datos, String campo) {
        String codigo = texto(datos,campo,20,true);
        if (db.primero("SELECT cod_almacen FROM almacen WHERE cod_almacen=:codigo AND estado='ACTIVO' FOR UPDATE",Map.of("codigo",codigo),"codigo").isEmpty()) {
            throw new BadRequestException("Selecciona un almacén activo existente.");
        }
        return codigo;
    }

    private Map<String,Object> stock(String producto, String almacen) {
        return db.primero("SELECT cantidad,costo_promedio FROM inventario_stock WHERE cod_pro=:producto AND cod_almacen=:almacen FOR UPDATE",
            Map.of("producto",producto,"almacen",almacen),"cantidad","costo");
    }

    private BigDecimal numero(Object valor) {
        return valor==null ? BigDecimal.ZERO : new BigDecimal(valor.toString());
    }

    private void retirar(String producto, String almacen, BigDecimal cantidad) {
        var actual = stock(producto,almacen);
        if (actual.isEmpty() || numero(actual.get("cantidad")).compareTo(cantidad)<0) {
            throw new ClientErrorException("La cantidad supera las existencias de ese almacén.",409);
        }
        db.ejecutar("UPDATE inventario_stock SET cantidad=cantidad-:cantidad WHERE cod_pro=:producto AND cod_almacen=:almacen",
            Map.of("cantidad",cantidad,"producto",producto,"almacen",almacen));
    }

    private void recibir(String producto, String almacen, BigDecimal cantidad, BigDecimal costo) {
        var actual = stock(producto,almacen);
        if (actual.isEmpty()) {
            db.ejecutar("INSERT INTO inventario_stock(cod_pro,cod_almacen,cantidad,costo_promedio) VALUES (:producto,:almacen,:cantidad,:costo)",
                valores("producto",producto,"almacen",almacen,"cantidad",cantidad,"costo",costo));
            return;
        }
        BigDecimal anterior = numero(actual.get("cantidad"));
        if (anterior.add(cantidad).compareTo(new BigDecimal("9999999999.99"))>0) throw new BadRequestException("La cantidad resultante excede el máximo permitido.");
        if (costo == null) {
            db.ejecutar("UPDATE inventario_stock SET cantidad=cantidad+:cantidad WHERE cod_pro=:producto AND cod_almacen=:almacen",
                Map.of("cantidad",cantidad,"producto",producto,"almacen",almacen));
            return;
        }
        BigDecimal nuevoCosto = (anterior.signum()==0 || actual.get("costo")==null)
            ? costo
            : anterior.multiply(numero(actual.get("costo"))).add(cantidad.multiply(costo))
                .divide(anterior.add(cantidad),2,RoundingMode.HALF_UP);
        db.ejecutar("UPDATE inventario_stock SET cantidad=cantidad+:cantidad,costo_promedio=:costo WHERE cod_pro=:producto AND cod_almacen=:almacen",
            valores("cantidad",cantidad,"costo",nuevoCosto,"producto",producto,"almacen",almacen));
    }

    private Map<String,Object> unidad(long id, String producto, String almacen) {
        var unidad = db.primero("""
            SELECT id_unidad,cod_pro,cod_almacen,fecha_baja,requiere_arreglo,requiere_cambio,requiere_baja,
                   estado_fisico,calificacion,codigo_unico FROM inventario_unidad WHERE id_unidad=:id FOR UPDATE
            """,Map.of("id",id),"id","producto","almacen","baja","arreglo","cambio","solicitaBaja","condicion","calificacion","codigo");
        if (unidad.isEmpty() || !Objects.equals(producto,unidad.get("producto"))) throw new BadRequestException("La unidad no pertenece al producto seleccionado.");
        if (!Objects.equals(almacen,unidad.get("almacen"))) throw new ClientErrorException("La unidad ya no está en el almacén de origen seleccionado.",409);
        if (unidad.get("baja")!=null) throw new ClientErrorException("La unidad tiene una baja registrada.",409);
        if (!db.lista("""
            SELECT d.id_detalle FROM prestamo_detalle d JOIN prestamo_concesion p ON p.cod_prestamo=d.cod_prestamo
            WHERE d.id_unidad=:id AND COALESCE(d.devuelto,0)=0 AND p.estado IN ('ACTIVO','PARCIAL')
            """,Map.of("id",id),"id").isEmpty()) throw new ClientErrorException("La unidad está en custodia. Registra su devolución antes de moverla.",409);
        return unidad;
    }

    private void registrar(Movimiento movimiento, AccesoService.Identidad user, String tipo, String origen, String destino,
                           String documentoTipo, String referencia, String producto, Long unidad, BigDecimal cantidad,
                           BigDecimal costo, String condicion, Integer calificacion, String detalleReferencia) {
        db.ejecutar("""
            INSERT INTO movimiento_inventario(cod_movimiento,tipo_movimiento,fecha,cod_almacen_origen,cod_almacen_destino,
                documento_tipo,documento_ref,observaciones)
            VALUES (:codigo,:tipo,:fecha,:origen,:destino,:docTipo,:referencia,:nota)
            """,valores("codigo",movimiento.codigo(),"tipo",tipo,"fecha",movimiento.fecha().atStartOfDay(),"origen",origen,
                "destino",destino,"docTipo",documentoTipo,"referencia",referencia,"nota",movimiento.nota()));
        db.ejecutar("""
            INSERT INTO movimiento_detalle(cod_movimiento,cod_pro,id_unidad,cantidad,precio_unitario,estado_entrega,estado_devolucion,observaciones)
            VALUES (:movimiento,:producto,:unidad,:cantidad,:costo,:entrega,:devolucion,:nota)
            """,valores("movimiento",movimiento.codigo(),"producto",producto,"unidad",unidad,"cantidad",cantidad,"costo",costo,
                "entrega",tipo.equals("DEVOLUCION")?null:condicion,"devolucion",tipo.equals("DEVOLUCION")?condicion:null,"nota",detalleReferencia));
        db.ejecutar("INSERT INTO auditoria(tabla,registro_id,accion,id_usuario,datos_despues) VALUES ('movimiento_inventario',:codigo,:tipo,:usuario,JSON_OBJECT('cantidad',:cantidad,'producto',:producto))",
            Map.of("codigo",movimiento.codigo(),"tipo",tipo,"usuario",user.id(),"cantidad",cantidad,"producto",producto));
        String doc = documentoTipo+"-"+movimiento.sufijo();
        db.ejecutar("""
            INSERT INTO documento_inventario(cod_doc,tipo,numero,fecha,proveedor,departamento,destino_a,observaciones,total)
            VALUES (:codigo,:tipo,:codigo,:fecha,'THAKI VOYAGE','Almacén',:destino,:nota,:total)
            """,valores("codigo",doc,"tipo",documentoTipo,"fecha",movimiento.fecha(),"destino",destino,"nota",movimiento.nota(),
                "total",costo==null?null:costo.multiply(cantidad)));
        db.ejecutar("""
            INSERT INTO documento_detalle(cod_doc,cod_pro,id_unidad,descripcion,cantidad,nota_observacion,estado_articulo,devuelto)
            SELECT :documento,p.cod_pro,:unidad,p.des_pro,:cantidad,:nota,:calificacion,:devuelto FROM producto p WHERE p.cod_pro=:producto
            """,valores("documento",doc,"producto",producto,"unidad",unidad,"cantidad",cantidad,"nota",movimiento.nota(),
                "calificacion",calificacion,"devuelto",tipo.equals("DEVOLUCION")?1:0));
    }

    @Transactional
    public Map<String,String> ingreso(AccesoService.Identidad user, Map<String,Object> datos) {
        Movimiento movimiento = movimiento(user,datos);
        String codigo = texto(datos,"producto",30,true);
        var producto = producto(codigo);
        comprobarRepeticion(movimiento);
        String destino = almacen(datos,"destino");
        BigDecimal cantidad = decimal(datos.get("cantidad"),true,true);
        BigDecimal costo = decimal(datos.get("costoUnitario"),false,false);
        Long idUnidad = null;
        String condicion = texto(datos,"estadoFisico",20,individual(producto));
        Integer calificacion = calificacion(datos.get("calificacion"),false);
        if (individual(producto)) {
            if (cantidad.compareTo(BigDecimal.ONE)!=0) throw new BadRequestException("Registra cada unidad individual con su código propio y cantidad uno.");
            String unico = unidades.registrar(codigo,new UnidadInventarioService.UnidadRequest(texto(datos,"codigoUnico",50,true),destino,
                condicion,calificacion,movimiento.fecha(),false,false,false,movimiento.nota()));
            idUnidad = ((Number)db.primero("SELECT id_unidad FROM inventario_unidad WHERE codigo_unico=:codigo",Map.of("codigo",unico),"id").get("id")).longValue();
        } else recibir(codigo,destino,cantidad,costo);
        if (costo!=null) db.ejecutar("INSERT INTO precio(cod_pro,cod_pre,fec_com,mon_pre,monto_pre) VALUES (:producto,:codigo,:fecha,'BOB',:costo)",
            Map.of("producto",codigo,"codigo","PRE-"+movimiento.sufijo(),"fecha",movimiento.fecha(),"costo",costo));
        registrar(movimiento,user,"INGRESO",null,destino,"NI","NI-"+movimiento.sufijo(),codigo,idUnidad,cantidad,costo,condicion,calificacion,null);
        return Map.of("documento","NI-"+movimiento.sufijo());
    }

    @Transactional
    public Map<String,String> trasladar(AccesoService.Identidad user, Map<String,Object> datos) {
        Movimiento movimiento = movimiento(user,datos);
        String codigo = texto(datos,"producto",30,true);
        var producto = producto(codigo);
        comprobarRepeticion(movimiento);
        String origen = almacen(datos,"origen");
        String destino = almacen(datos,"destino");
        if (origen.equals(destino)) throw new BadRequestException("Selecciona un almacén de destino distinto del origen.");
        BigDecimal cantidad = decimal(datos.get("cantidad"),true,true);
        Long idUnidad = null;
        BigDecimal costo = null;
        if (individual(producto)) {
            idUnidad = id(datos.get("unidad"));
            unidad(idUnidad,codigo,origen);
            if (cantidad.compareTo(BigDecimal.ONE)!=0) throw new BadRequestException("El traslado individual debe tener cantidad uno.");
            db.ejecutar("UPDATE inventario_unidad SET cod_almacen=:destino WHERE id_unidad=:id",Map.of("destino",destino,"id",idUnidad));
        } else {
            costo = stock(codigo,origen).get("costo")==null?null:numero(stock(codigo,origen).get("costo"));
            retirar(codigo,origen,cantidad);
            recibir(codigo,destino,cantidad,costo);
        }
        registrar(movimiento,user,"TRASLADO",origen,destino,"TR","TR-"+movimiento.sufijo(),codigo,idUnidad,cantidad,costo,null,null,null);
        return Map.of("documento","TR-"+movimiento.sufijo());
    }

    @Transactional
    public Map<String,String> prestar(AccesoService.Identidad user, Map<String,Object> datos) {
        Movimiento movimiento = movimiento(user,datos);
        String codigo = texto(datos,"producto",30,true);
        var producto = producto(codigo);
        comprobarRepeticion(movimiento);
        if (marcado(producto.get("consumible"))) throw new BadRequestException("Los consumibles no se registran como préstamos retornables.");
        String origen = almacen(datos,"origen");
        String persona = texto(datos,"persona",20,true);
        if (db.primero("SELECT cod_persona FROM persona WHERE cod_persona=:codigo",Map.of("codigo",persona),"codigo").isEmpty()) throw new BadRequestException("Selecciona la persona que recibe el material.");
        LocalDate esperada = fecha(datos,"fechaEsperada",true,true);
        if (esperada.isBefore(movimiento.fecha())) throw new BadRequestException("La devolución esperada debe ser igual o posterior a la entrega.");
        BigDecimal cantidad = decimal(datos.get("cantidad"),true,true);
        Long idUnidad = null;
        String condicion = null;
        BigDecimal costo = null;
        if (individual(producto)) {
            idUnidad = id(datos.get("unidad"));
            var unidad = unidad(idUnidad,codigo,origen);
            if (cantidad.compareTo(BigDecimal.ONE)!=0) throw new BadRequestException("Selecciona una unidad y cantidad uno.");
            if (unidad.get("condicion")==null || unidad.get("calificacion")==null || unidad.get("codigo")==null
                || marcado(unidad.get("arreglo")) || marcado(unidad.get("cambio")) || marcado(unidad.get("solicitaBaja"))) {
                throw new ClientErrorException("Completa la evaluación del equipo y resuelve su atención pendiente antes de prestarlo.",409);
            }
            condicion = unidad.get("condicion").toString();
        } else {
            var actual = stock(codigo,origen);
            costo = actual.get("costo")==null?null:numero(actual.get("costo"));
            retirar(codigo,origen,cantidad);
        }
        String prestamo = "PRS-"+movimiento.sufijo();
        registrar(movimiento,user,"PRESTAMO",origen,null,"NS","NS-"+movimiento.sufijo(),codigo,idUnidad,cantidad,costo,condicion,null,null);
        db.ejecutar("""
            INSERT INTO prestamo_concesion(cod_prestamo,tipo,cod_persona,fecha_prestamo,fecha_dev_esperada,estado,cod_doc_salida,observaciones)
            VALUES (:codigo,'PRESTAMO',:persona,:fecha,:esperada,'ACTIVO',:documento,:nota)
            """,valores("codigo",prestamo,"persona",persona,"fecha",movimiento.fecha(),"esperada",esperada,"documento","NS-"+movimiento.sufijo(),"nota",movimiento.nota()));
        String metadata;
        try { metadata = json.writeValueAsString(valores("almacenOrigen",origen,"costoUnitario",costo)); }
        catch (JsonProcessingException error) { throw new IllegalStateException(error); }
        db.ejecutar("INSERT INTO prestamo_detalle(cod_prestamo,cod_pro,id_unidad,cantidad,estado_entrega,devuelto,observaciones) VALUES (:prestamo,:producto,:unidad,:cantidad,:condicion,0,:metadata)",
            valores("prestamo",prestamo,"producto",codigo,"unidad",idUnidad,"cantidad",cantidad,"condicion",condicion,"metadata",metadata));
        return Map.of("documento","NS-"+movimiento.sufijo(),"prestamo",prestamo);
    }

    public BigDecimal devuelta(String prestamo, long detalle) {
        var fila = db.primero("""
            SELECT COALESCE(SUM(d.cantidad),0) FROM movimiento_detalle d
            JOIN movimiento_inventario m ON m.cod_movimiento=d.cod_movimiento
            WHERE m.tipo_movimiento='DEVOLUCION' AND m.documento_tipo='PRESTAMO'
              AND m.documento_ref=:prestamo AND d.observaciones=:marca
            """,Map.of("prestamo",prestamo,"marca","PRESTAMO_DETALLE="+detalle),"cantidad");
        return numero(fila.get("cantidad"));
    }

    @Transactional
    public Map<String,String> devolver(AccesoService.Identidad user, String prestamo, Map<String,Object> datos) {
        Movimiento movimiento = movimiento(user,datos);
        // Producto, préstamo y detalle se bloquean en ese orden en las operaciones de retorno.
        long detalle = id(datos.get("detalle"));
        var referencia = db.primero("SELECT cod_pro FROM prestamo_detalle WHERE id_detalle=:id AND cod_prestamo=:prestamo",Map.of("id",detalle,"prestamo",prestamo),"producto");
        if (referencia.isEmpty()) throw new NotFoundException("Detalle de préstamo no encontrado.");
        String codigo = referencia.get("producto").toString();
        // Las devoluciones admiten productos desactivados para conservar su custodia.
        db.lista("SELECT cod_pro FROM producto WHERE cod_pro=:codigo FOR UPDATE",Map.of("codigo",codigo),"codigo");
        var cabecera = db.primero("SELECT fecha_prestamo,estado FROM prestamo_concesion WHERE cod_prestamo=:codigo FOR UPDATE",Map.of("codigo",prestamo),"fecha","estado");
        if (cabecera.isEmpty()) throw new NotFoundException("Préstamo no encontrado.");
        comprobarRepeticion(movimiento);
        var fila = db.primero("SELECT cod_pro,id_unidad,cantidad,devuelto,observaciones FROM prestamo_detalle WHERE id_detalle=:id AND cod_prestamo=:prestamo FOR UPDATE",Map.of("id",detalle,"prestamo",prestamo),"producto","unidad","cantidad","devuelto","metadata");
        if (fila.isEmpty()) throw new NotFoundException("Detalle de préstamo no encontrado.");
        if (!Set.of("ACTIVO","PARCIAL").contains(Objects.toString(cabecera.get("estado"),"")) || marcado(fila.get("devuelto"))) throw new ClientErrorException("Este material ya no tiene una devolución pendiente.",409);
        if (movimiento.fecha().isBefore(LocalDate.parse(cabecera.get("fecha").toString()))) throw new BadRequestException("La devolución no puede ser anterior a la entrega.");
        String destino = almacen(datos,"destino");
        BigDecimal cantidad = decimal(datos.get("cantidad"),true,true);
        BigDecimal pendiente = numero(fila.get("cantidad")).subtract(devuelta(prestamo,detalle));
        if (cantidad.compareTo(pendiente)>0) throw new ClientErrorException("La cantidad supera el saldo pendiente de devolución.",409);
        String condicion = texto(datos,"estadoFisico",20,true);
        if (!Set.of("BUENO","REGULAR","DANADO").contains(condicion)) throw new BadRequestException("Selecciona una condición de devolución válida.");
        Integer calificacion = calificacion(datos.get("calificacion"),true);
        boolean arreglo = marcado(datos.get("requiereArreglo")) || condicion.equals("DANADO");
        boolean cambio = marcado(datos.get("requiereCambio"));
        boolean baja = marcado(datos.get("requiereBaja"));
        Long idUnidad = fila.get("unidad")==null?null:((Number)fila.get("unidad")).longValue();
        if (idUnidad!=null) {
            if (cantidad.compareTo(BigDecimal.ONE)!=0) throw new BadRequestException("Una unidad individual se devuelve completa, con cantidad uno.");
            db.lista("SELECT id_unidad FROM inventario_unidad WHERE id_unidad=:id FOR UPDATE",Map.of("id",idUnidad),"id");
            db.ejecutar("UPDATE inventario_unidad SET cod_almacen=:almacen,estado_fisico=:condicion,calificacion=:calificacion,requiere_arreglo=:arreglo,requiere_cambio=:cambio,requiere_baja=:baja WHERE id_unidad=:id",
                Map.of("almacen",destino,"condicion",condicion,"calificacion",calificacion,"arreglo",arreglo,"cambio",cambio,"baja",baja,"id",idUnidad));
        } else {
            if (!condicion.equals("BUENO") || arreglo || cambio || baja) throw new BadRequestException("Devuelve por cantidad únicamente el material apto. Conserva el saldo pendiente y registra los daños en las observaciones.");
            BigDecimal costo = null;
            try {
                var metadata = json.readTree(Objects.toString(fila.get("metadata"),"{}"));
                if (metadata.hasNonNull("costoUnitario")) costo = decimal(metadata.get("costoUnitario").asText(),false,false);
            } catch (JsonProcessingException ignored) { /* Los préstamos antiguos pueden tener notas en texto libre. */ }
            recibir(codigo,destino,cantidad,costo);
        }
        boolean completo = cantidad.compareTo(pendiente)==0;
        db.ejecutar("UPDATE prestamo_detalle SET devuelto=:devuelto,estado_devolucion=:condicion WHERE id_detalle=:id",Map.of("devuelto",completo?1:0,"condicion",condicion,"id",detalle));
        var restantes = db.lista("SELECT id_detalle FROM prestamo_detalle WHERE cod_prestamo=:codigo AND COALESCE(devuelto,0)=0",Map.of("codigo",prestamo),"id");
        db.ejecutar("UPDATE prestamo_concesion SET estado=:estado,fecha_dev_real=:fecha WHERE cod_prestamo=:codigo",valores("estado",restantes.isEmpty()?"DEVUELTO":"PARCIAL","fecha",restantes.isEmpty()?movimiento.fecha():null,"codigo",prestamo));
        registrar(movimiento,user,"DEVOLUCION",null,destino,"NI",prestamo,codigo,idUnidad,cantidad,null,condicion,calificacion,"PRESTAMO_DETALLE="+detalle);
        db.ejecutar("UPDATE movimiento_inventario SET documento_tipo='PRESTAMO' WHERE cod_movimiento=:codigo",Map.of("codigo",movimiento.codigo()));
        return Map.of("documento","NI-"+movimiento.sufijo(),"prestamo",prestamo);
    }
}
