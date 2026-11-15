package com.thakivoyage.service;

import static com.thakivoyage.service.PortalQuery.valores;
import java.math.BigDecimal;
import java.time.*;
import java.util.*;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.*;

@ApplicationScoped
public class CatalogoViajeService {
    @Inject PortalQuery db;
    record Campo(String nombre, String columna, String tipo, int max, boolean requerido, String referencia, boolean inmutable, Set<String> opciones) {}
    record Modulo(String tabla, String clave, String prefijo, List<Campo> campos) {}
    static Campo texto(String nombre,String columna,int max,boolean requerido) { return new Campo(nombre,columna,"texto",max,requerido,null,false,Set.of()); }
    static Campo numero(String nombre,String columna,int max,boolean requerido) { return new Campo(nombre,columna,"entero",max,requerido,null,false,Set.of()); }
    static Campo fecha(String nombre,String columna,boolean requerido) { return new Campo(nombre,columna,"fecha",0,requerido,null,false,Set.of()); }
    static Campo dinero(String nombre,String columna,boolean requerido) { return new Campo(nombre,columna,"dinero",0,requerido,null,false,Set.of()); }
    static Campo referencia(String nombre,String columna,String modulo) { return new Campo(nombre,columna,"texto",20,true,modulo,true,Set.of()); }
    static Campo opciones(String nombre,String columna,String... opciones) { return new Campo(nombre,columna,"texto",50,true,null,false,Set.of(opciones)); }
    private static final Map<String,Modulo> MODULOS = Map.of(
        "hoteles",new Modulo("hotel","cod_hotel","HOT",List.of(
            texto("nombre","nombre",150,true),texto("descripcion","descripcion",4000,false),numero("categoria","categoria",5,true),
            texto("ciudad","ciudad",100,true),texto("pais","pais",80,true),texto("direccion","direccion",200,true),
            texto("telefono","telefono",50,false),texto("email","email",150,false),texto("sitioWeb","sitio_web",200,false),
            texto("imagenUrl","imagen_url",300,false),texto("servicios","servicios",4000,false),
            new Campo("checkIn","check_in","hora",0,false,null,false,Set.of()),new Campo("checkOut","check_out","hora",0,false,null,false,Set.of()),
            opciones("estado","estado","ACTIVO","INACTIVO"))),
        "habitaciones",new Modulo("hotel_tipo_habitacion","cod_tipo_hab","HAB",List.of(
            referencia("codHotel","cod_hotel","hoteles"),texto("nombre","nombre",100,true),texto("descripcion","descripcion",4000,false),
            numero("capacidad","capacidad",1000,true),numero("numCamas","num_camas",1000,true),texto("tipoCamas","tipo_camas",100,false),
            opciones("estado","estado","ACTIVO","INACTIVO"))),
        "tarifas-hotel",new Modulo("hotel_tarifa","cod_tarifa","TAR",List.of(
            referencia("codTipoHab","cod_tipo_hab","habitaciones"),fecha("fechaInicio","fecha_inicio",true),fecha("fechaFin","fecha_fin",false),
            opciones("moneda","moneda","BOB","USD","EUR"),dinero("precioNoche","precio_noche",true),
            new Campo("incluyeDesayuno","incluye_desayuno","booleano",0,true,null,false,Set.of()),texto("observaciones","observaciones",4000,false))),
        "vehiculos",new Modulo("vehiculo","cod_vehiculo","VEH",List.of(
            texto("placa","placa",20,true),texto("marca","marca",80,true),texto("modelo","modelo",80,true),numero("anio","anio",Year.now().getValue()+1,false),
            texto("color","color",50,false),numero("capacidad","capacidad",1000,true),texto("tipo","tipo",50,true),
            opciones("estado","estado","DISPONIBLE","MANTENIMIENTO","INACTIVO","EN_USO"),texto("observaciones","observaciones",4000,false))),
        "conductores",new Modulo("conductor","cod_conductor","CON",List.of(
            referencia("codEmpleado","cod_empleado","empleados"),texto("licencia","licencia",50,true),texto("categoriaLic","categoria_lic",10,true),
            fecha("vencimientoLic","vencimiento_lic",true),numero("experienciaAnios","experiencia_anios",80,false),opciones("estado","estado","ACTIVO","INACTIVO"))),
        "servicios-transporte",new Modulo("servicio_transporte","cod_servicio","TRS",List.of(
            texto("nombre","nombre",150,true),texto("descripcion","descripcion",4000,false),texto("tipo","tipo",50,true),
            texto("origen","origen",150,true),texto("destino","destino",150,true),dinero("distanciaKm","distancia_km",false),
            numero("duracionMin","duracion_min",100000,false),dinero("precioBase","precio_base",true),opciones("moneda","moneda","BOB","USD","EUR"),
            numero("capacidadMax","capacidad_max",1000,true),opciones("estado","estado","ACTIVO","INACTIVO")))
    );

    private Modulo modulo(String nombre) {
        var modulo=MODULOS.get(nombre);
        if(modulo==null) throw new NotFoundException("Catálogo no encontrado.");
        return modulo;
    }

    private String select(Modulo modulo) {
        return "SELECT "+modulo.clave()+","+String.join(",",modulo.campos().stream().map(c->c.tipo().equals("hora") ? "TIME_FORMAT("+c.columna()+",'%H:%i')" : c.columna()).toList())+" FROM "+modulo.tabla();
    }

    private String[] columnas(Modulo modulo) {
        var nombres=new ArrayList<String>(); nombres.add("codigo"); modulo.campos().forEach(c->nombres.add(c.nombre())); return nombres.toArray(String[]::new);
    }

    public List<Map<String,Object>> listar(String nombre) {
        var m=modulo(nombre); return db.lista(select(m)+" ORDER BY "+m.clave(),Map.of(),columnas(m));
    }

    public Map<String,Object> obtener(String nombre,String codigo) {
        var m=modulo(nombre); var fila=db.primero(select(m)+" WHERE "+m.clave()+"=:codigo",Map.of("codigo",codigo),columnas(m));
        if(fila.isEmpty()) throw new NotFoundException("Registro no encontrado."); return fila;
    }

    Map<String,Object> validar(String nombre,Map<String,Object> datos,Map<String,Object> anterior) {
        var m=modulo(nombre);
        if(datos==null) throw new BadRequestException("Completa los datos del catálogo.");
        Map<String,Object> salida=new LinkedHashMap<>();
        for(var c:m.campos()) {
            Object recibido=datos.get(c.nombre()); Object valor=null;
            String texto=recibido==null ? "" : recibido.toString().strip();
            if(texto.isEmpty()) { if(c.requerido()) throw new BadRequestException("Completa el campo "+c.nombre()+"."); }
            else try {
                valor=switch(c.tipo()) {
                    case "entero" -> { int n=new BigDecimal(texto).intValueExact(); int min=Set.of("capacidad","numCamas","capacidadMax").contains(c.nombre())?1:0;
                        if(n<min || n>c.max() || (c.nombre().equals("anio") && n<1900)) throw new IllegalArgumentException(); yield n; }
                    case "dinero" -> { var n=new BigDecimal(texto); if(n.signum()<0 || n.scale()>2 || n.compareTo(new BigDecimal(c.nombre().equals("distanciaKm")?"99999999.99":"9999999999.99"))>0) throw new IllegalArgumentException(); yield n; }
                    case "fecha" -> LocalDate.parse(texto);
                    case "hora" -> LocalTime.parse(texto);
                    case "booleano" -> { if(!Set.of("true","false","1","0").contains(texto)) throw new IllegalArgumentException(); yield Set.of("true","1").contains(texto)?1:0; }
                    default -> { if(!(recibido instanceof String) || texto.length()>c.max()) throw new IllegalArgumentException(); yield texto; }
                };
            } catch(RuntimeException e) { throw new BadRequestException("Revisa el valor de "+c.nombre()+"."); }
            if(!c.opciones().isEmpty() && !c.opciones().contains(texto)) throw new BadRequestException("Selecciona una opción válida para "+c.nombre()+".");
            if(c.inmutable() && !anterior.isEmpty() && !Objects.equals(anterior.get(c.nombre()),valor)) throw new BadRequestException("No puedes cambiar el vínculo "+c.nombre()+" de este registro.");
            if(c.referencia()!=null && valor!=null) {
                String tabla=c.referencia().equals("empleados")?"empleado":modulo(c.referencia()).tabla();
                String clave=c.referencia().equals("empleados")?"cod_empleado":modulo(c.referencia()).clave();
                if(db.primero("SELECT "+clave+" FROM "+tabla+" WHERE "+clave+"=:codigo",Map.of("codigo",valor),"codigo").isEmpty()) throw new BadRequestException("La referencia "+c.nombre()+" no existe.");
            }
            salida.put(c.nombre(),valor);
        }
        for(String correo:List.of("email")) if(salida.get(correo)!=null && !salida.get(correo).toString().matches("^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$")) throw new BadRequestException("Introduce un correo válido.");
        for(String enlace:List.of("sitioWeb","imagenUrl")) if(salida.get(enlace)!=null && !salida.get(enlace).toString().matches("https?://[^\\s]+")) throw new BadRequestException("La dirección web debe empezar con https:// o http://.");
        if(nombre.equals("tarifas-hotel") && salida.get("fechaFin")!=null && ((LocalDate)salida.get("fechaFin")).isBefore((LocalDate)salida.get("fechaInicio"))) throw new BadRequestException("La tarifa no puede terminar antes de su inicio.");
        if(nombre.equals("conductores")) {
            var empleado=db.primero("SELECT estado,es_conductor FROM empleado WHERE cod_empleado=:codigo",Map.of("codigo",salida.get("codEmpleado")),"estado","conductor");
            boolean habilitado=Boolean.TRUE.equals(empleado.get("conductor")) || empleado.get("conductor") instanceof Number n && n.intValue()==1;
            if("ACTIVO".equals(salida.get("estado")) && (!"ACTIVO".equals(empleado.get("estado")) || !habilitado || ((LocalDate)salida.get("vencimientoLic")).isBefore(LocalDate.now()))) throw new BadRequestException("Un conductor activo necesita empleado activo con función de conductor y licencia vigente.");
        }
        return salida;
    }

    @Transactional
    public Map<String,Object> guardar(String nombre,String codigo,Map<String,Object> datos) {
        var m=modulo(nombre); var anterior=codigo==null?Map.<String,Object>of():obtener(nombre,codigo);
        var valores=validar(nombre,datos,anterior);
        if(nombre.equals("vehiculos") || nombre.equals("conductores")) {
            String campo=nombre.equals("vehiculos")?"placa":"codEmpleado";
            String columna=nombre.equals("vehiculos")?"placa":"cod_empleado";
            var repetidos=db.lista("SELECT "+m.clave()+" FROM "+m.tabla()+" WHERE "+columna+"=:valor"+(codigo==null?"":" AND "+m.clave()+"<>:codigo"),codigo==null?Map.of("valor",valores.get(campo)):Map.of("valor",valores.get(campo),"codigo",codigo),"codigo");
            if(!repetidos.isEmpty()) throw new ClientErrorException("Ya existe un registro para "+campo+".",409);
        }
        if(nombre.equals("tarifas-hotel")) {
            db.lista("SELECT cod_tipo_hab FROM hotel_tipo_habitacion WHERE cod_tipo_hab=:codigo FOR UPDATE",Map.of("codigo",valores.get("codTipoHab")),"codigo");
            var params=valores("habitacion",valores.get("codTipoHab"),"inicio",valores.get("fechaInicio"),"fin",valores.get("fechaFin"),"codigo",codigo==null?"":codigo);
            if(!db.lista("SELECT cod_tarifa FROM hotel_tarifa WHERE cod_tipo_hab=:habitacion AND cod_tarifa<>:codigo AND (fecha_fin IS NULL OR fecha_fin>=:inicio) AND (:fin IS NULL OR fecha_inicio<=:fin)",params,"codigo").isEmpty()) throw new ClientErrorException("Ya hay una tarifa para esa habitación dentro de estas fechas. Ajusta su vigencia antes de añadir otra.",409);
        }
        String clave=codigo==null?GestionValidationService.codigo(m.prefijo()):codigo;
        valores.put("codigo",clave);
        if(codigo==null) db.ejecutar("INSERT INTO "+m.tabla()+" ("+m.clave()+","+String.join(",",m.campos().stream().map(Campo::columna).toList())+") VALUES (:codigo,"+String.join(",",m.campos().stream().map(c->":"+c.nombre()).toList())+")",valores);
        else db.ejecutar("UPDATE "+m.tabla()+" SET "+String.join(",",m.campos().stream().map(c->c.columna()+"=:"+c.nombre()).toList())+" WHERE "+m.clave()+"=:codigo",valores);
        return obtener(nombre,clave);
    }
}
