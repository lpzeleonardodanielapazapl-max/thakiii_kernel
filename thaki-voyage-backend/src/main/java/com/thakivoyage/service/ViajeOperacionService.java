package com.thakivoyage.service;

import static com.thakivoyage.service.PortalQuery.valores;
import java.math.BigDecimal;
import java.util.*;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.*;

@ApplicationScoped
public class ViajeOperacionService {
    @Inject PortalQuery db;
    @Inject PortalService portal;
    @Inject AtencionService atencion;

    public record CierreViaje(String solicitud, String titulo, String descripcion, BigDecimal precio, String moneda,
        String incluye, Integer dias) {}
    public record PersonalAsignacion(String empleado, String rol, String notas) {}
    public record UtileriaAsignacion(String producto, Integer cantidad, String notas) {}
    public record PagoCliente(String tipo, String metodo, String titular, String referencia) {}

    private boolean admin(AccesoService.Identidad user) { return "ADMIN".equals(user.rol()); }

    public Map<String,Object> contextoChat(AccesoService.Identidad user, long conversacionId) {
        var conversacion = atencion.conversacion(user, conversacionId);
        String clienteCodigo = Objects.toString(conversacion.get("clienteCodigo"), "");
        if (clienteCodigo.isBlank()) return valores("conversacion", conversacion, "solicitudes", List.of(), "reservas", List.of());
        var solicitudes = db.lista("""
            SELECT s.cod_solicitud,s.fecha_inicio,s.fecha_fin,s.num_personas,s.presupuesto,s.moneda,s.intereses,s.estado,
              (SELECT COUNT(*) FROM propuesta_viaje p WHERE p.cod_solicitud=s.cod_solicitud AND p.estado='ENVIADA'),
              (SELECT COUNT(*) FROM propuesta_viaje p WHERE p.cod_solicitud=s.cod_solicitud AND p.estado='ACEPTADA')
            FROM solicitud_viaje s WHERE s.cod_cliente=:cliente ORDER BY s.fecha_solicitud DESC LIMIT 10
            """, Map.of("cliente", clienteCodigo), "codigo","fechaInicio","fechaFin","personas","presupuesto","moneda","intereses","estado","propuestasEnviadas","propuestasAceptadas");
        var reservas = db.lista("""
            SELECT r.cod_reserva,r.estado,r.total,r.moneda,r.fecha_inicio,r.fecha_fin,v.cod_viaje
            FROM reserva r JOIN viaje_confirmado v ON v.cod_reserva=r.cod_reserva
            WHERE r.cod_cliente=:cliente ORDER BY r.fecha_reserva DESC LIMIT 10
            """, Map.of("cliente", clienteCodigo), "codigo","estado","total","moneda","fechaInicio","fechaFin","viaje");
        return valores("conversacion", conversacion, "solicitudes", solicitudes, "reservas", reservas);
    }

    @Transactional
    public Map<String,Object> cerrarYHabilitarPago(AccesoService.Identidad user, CierreViaje datos) {
        if (datos == null) throw new BadRequestException("Completa el formulario del viaje.");
        String solicitud = AccesoService.texto(datos.solicitud(), "la solicitud", 20);
        var s = atencion.solicitud(user, solicitud);
        if (!db.lista("SELECT cod_propuesta FROM propuesta_viaje WHERE cod_solicitud=:codigo AND estado='ACEPTADA'", Map.of("codigo", solicitud), "codigo").isEmpty()) {
            throw new ClientErrorException("Esta solicitud ya tiene un viaje con pago habilitado.", 409);
        }
        String titulo = AccesoService.texto(datos.titulo(), "el título", 200);
        String incluye = AccesoService.texto(datos.incluye() == null || datos.incluye().isBlank() ? "Servicios acordados con el cliente." : datos.incluye(), "lo incluido", 4000);
        String descripcion = AccesoService.texto(
            (datos.descripcion() == null || datos.descripcion().isBlank() ? "Propuesta cerrada con el cliente." : datos.descripcion()) + "\n\nIncluye:\n" + incluye,
            "la descripción", 4000);
        if (datos.precio() == null || datos.precio().signum() <= 0 || datos.precio().scale() > 2 || datos.precio().compareTo(new BigDecimal("9999999999.99")) > 0) {
            throw new BadRequestException("Introduce un precio válido.");
        }
        if (!Set.of("BOB", "USD", "EUR").contains(datos.moneda() == null ? "" : datos.moneda())) {
            throw new BadRequestException("Selecciona la moneda.");
        }
        int dias = datos.dias() == null ? 1 : datos.dias();
        if (dias < 1 || dias > 120) throw new BadRequestException("Indica entre 1 y 120 días de itinerario.");
        db.lista("SELECT cod_solicitud FROM solicitud_viaje WHERE cod_solicitud=:codigo FOR UPDATE", Map.of("codigo", solicitud), "codigo");
        var version = db.primero("SELECT COALESCE(MAX(version),0)+1 FROM propuesta_viaje WHERE cod_solicitud=:codigo", Map.of("codigo", solicitud), "numero");
        String propuesta = GestionValidationService.codigo("PRP");
        db.ejecutar("UPDATE propuesta_viaje SET estado='REEMPLAZADA' WHERE cod_solicitud=:codigo AND estado='ENVIADA'", Map.of("codigo", solicitud));
        db.ejecutar("""
            INSERT INTO propuesta_viaje (cod_propuesta,cod_solicitud,version,titulo,descripcion,duracion_dias,precio_total,moneda,estado)
            VALUES (:codigo,:solicitud,:version,:titulo,:descripcion,:dias,:precio,:moneda,'ENVIADA')
            """, valores("codigo", propuesta, "solicitud", solicitud, "version", version.get("numero"), "titulo", titulo,
            "descripcion", descripcion, "dias", dias, "precio", datos.precio(), "moneda", datos.moneda()));
        for (int i = 1; i <= dias; i++) {
            db.ejecutar("INSERT INTO propuesta_detalle (cod_propuesta,dia,descripcion,servicios) VALUES (:codigo,:dia,:descripcion,:servicios)",
                valores("codigo", propuesta, "dia", i, "descripcion", "Día " + i + " · recorrido acordado con el cliente.", "servicios", incluye));
        }
        var aceptacion = atencion.aceptar(user, propuesta);
        portal.notificar(((Number) s.get("clienteId")).longValue(), "Pasarela de pago habilitada",
            "Tu asesor cerró el viaje. Ya puedes pagar el anticipo (50 %) o el total desde Mi viaje → Pagar.");
        var chat = db.primero("""
            SELECT c.cod_conversacion FROM conversacion c JOIN usuario u ON u.id_usuario=c.id_usuario_cliente
            WHERE u.email=:email AND c.estado='ABIERTA' AND (:admin=1 OR c.id_usuario_asesor=:usuario)
            ORDER BY COALESCE(c.fecha_ultimo_msg,c.fecha_inicio) DESC LIMIT 1
            """, valores("email", s.get("email"), "admin", admin(user) ? 1 : 0, "usuario", user.id()), "codigo");
        if (!chat.isEmpty()) {
            portal.insertarMensaje(((Number) chat.get("codigo")).longValue(), user.id(),
                "Cerramos tu viaje \"" + titulo + "\". Habilitamos la pasarela de pagos: puedes abonar el anticipo del 50 % o el total. Reserva " + aceptacion.get("reserva") + ".");
        }
        return Map.of("propuesta", propuesta, "reserva", aceptacion.get("reserva"), "mensaje", "Viaje registrado y pasarela de pago habilitada para el cliente.");
    }

    public List<Map<String,Object>> viajesConfirmados(AccesoService.Identidad user) {
        return db.lista("""
            SELECT v.cod_viaje,r.cod_reserva,p.titulo,CONCAT(per.nombres,' ',per.apellidos),c.cod_cliente,
                   r.fecha_inicio,r.fecha_fin,r.num_personas,r.estado,r.total,r.moneda,v.estado,
                   (SELECT COUNT(*) FROM viaje_personal vp WHERE vp.cod_viaje=v.cod_viaje),
                   (SELECT COUNT(*) FROM viaje_utileria vu WHERE vu.cod_viaje=v.cod_viaje)
            FROM viaje_confirmado v
            JOIN reserva r ON r.cod_reserva=v.cod_reserva
            JOIN propuesta_viaje p ON p.cod_propuesta=v.cod_propuesta
            JOIN solicitud_viaje s ON s.cod_solicitud=p.cod_solicitud
            JOIN cliente c ON c.cod_cliente=s.cod_cliente
            JOIN persona per ON per.cod_persona=c.cod_persona
            WHERE v.estado='CONFIRMADO' AND r.estado='CONFIRMADA'
              AND (:admin=1 OR EXISTS(SELECT 1 FROM usuario u JOIN asignacion_asesor a ON a.id_usuario_cli=u.id_usuario
                  WHERE u.email=per.email AND a.activa=1 AND a.id_usuario_ase=:usuario))
            ORDER BY r.fecha_inicio, v.cod_viaje
            """, Map.of("admin", admin(user) ? 1 : 0, "usuario", user.id()),
            "codigo","reserva","titulo","cliente","clienteCodigo","fechaInicio","fechaFin","personas","estadoReserva","total","moneda","estado","personal","utileria");
    }

    public Map<String,Object> viaje(AccesoService.Identidad user, String codigo) {
        var viaje = db.primero("""
            SELECT v.cod_viaje,r.cod_reserva,p.cod_propuesta,p.titulo,p.descripcion,CONCAT(per.nombres,' ',per.apellidos),
                   c.cod_cliente,r.fecha_inicio,r.fecha_fin,r.num_personas,r.estado,r.total,r.moneda,v.estado
            FROM viaje_confirmado v
            JOIN reserva r ON r.cod_reserva=v.cod_reserva
            JOIN propuesta_viaje p ON p.cod_propuesta=v.cod_propuesta
            JOIN solicitud_viaje s ON s.cod_solicitud=p.cod_solicitud
            JOIN cliente c ON c.cod_cliente=s.cod_cliente
            JOIN persona per ON per.cod_persona=c.cod_persona
            WHERE v.cod_viaje=:codigo AND v.estado='CONFIRMADO'
              AND (:admin=1 OR EXISTS(SELECT 1 FROM usuario u JOIN asignacion_asesor a ON a.id_usuario_cli=u.id_usuario
                  WHERE u.email=per.email AND a.activa=1 AND a.id_usuario_ase=:usuario))
            """, Map.of("codigo", codigo, "admin", admin(user) ? 1 : 0, "usuario", user.id()),
            "codigo","reserva","propuesta","titulo","descripcion","cliente","clienteCodigo","fechaInicio","fechaFin","personas","estadoReserva","total","moneda","estado");
        if (viaje.isEmpty()) throw new NotFoundException("Viaje confirmado no encontrado.");
        viaje.put("personal", db.lista("""
            SELECT vp.id_asignacion,vp.cod_empleado,CONCAT(p.nombres,' ',p.apellidos),vp.rol_en_viaje,vp.notas,
                   e.es_guia,e.es_conductor,e.es_cocinero
            FROM viaje_personal vp JOIN empleado e ON e.cod_empleado=vp.cod_empleado
            JOIN persona p ON p.cod_persona=e.cod_persona WHERE vp.cod_viaje=:codigo ORDER BY vp.rol_en_viaje,p.nombres
            """, Map.of("codigo", codigo), "id","empleado","nombre","rol","notas","guia","conductor","cocinero"));
        viaje.put("utileria", db.lista("""
            SELECT vu.id_utileria,vu.cod_producto,pr.des_pro,vu.cantidad,vu.notas
            FROM viaje_utileria vu JOIN producto pr ON pr.cod_pro=vu.cod_producto
            WHERE vu.cod_viaje=:codigo ORDER BY pr.des_pro
            """, Map.of("codigo", codigo), "id","producto","nombre","cantidad","notas"));
        viaje.put("personalDisponible", personalDisponible(user, codigo));
        viaje.put("utileriaDisponible", utileriaDisponible(user, codigo));
        return viaje;
    }

    public List<Map<String,Object>> personalDisponible(AccesoService.Identidad user, String viaje) {
        var v = viajeBase(user, viaje);
        return db.lista("""
            SELECT e.cod_empleado,CONCAT(p.nombres,' ',p.apellidos),e.es_guia,e.es_conductor,e.es_cocinero,e.es_asesor,
              (SELECT COUNT(*) FROM viaje_personal vp JOIN viaje_confirmado vc ON vc.cod_viaje=vp.cod_viaje
                WHERE vp.cod_empleado=e.cod_empleado AND vc.estado='CONFIRMADO'
                  AND vc.fecha_inicio<=:fin AND vc.fecha_fin>=:inicio AND vc.cod_viaje<>:viaje)
            FROM empleado e JOIN persona p ON p.cod_persona=e.cod_persona
            WHERE e.estado='ACTIVO' AND (e.es_guia=1 OR e.es_conductor=1 OR e.es_cocinero=1)
              AND NOT EXISTS(SELECT 1 FROM viaje_personal x WHERE x.cod_viaje=:viaje AND x.cod_empleado=e.cod_empleado)
            ORDER BY p.nombres,p.apellidos
            """, valores("viaje", viaje, "inicio", v.get("fechaInicio"), "fin", v.get("fechaFin")),
            "codigo","nombre","guia","conductor","cocinero","asesor","viajesSolapados");
    }

    public List<Map<String,Object>> utileriaDisponible(AccesoService.Identidad user, String viaje) {
        var v = viajeBase(user, viaje);
        return db.lista("""
            SELECT pr.cod_pro,pr.des_pro,COALESCE(SUM(st.cantidad),0),
              COALESCE((SELECT SUM(vu.cantidad) FROM viaje_utileria vu JOIN viaje_confirmado vc ON vc.cod_viaje=vu.cod_viaje
                WHERE vu.cod_producto=pr.cod_pro AND vc.estado='CONFIRMADO'
                  AND vc.fecha_inicio<=:fin AND vc.fecha_fin>=:inicio AND vc.cod_viaje<>:viaje),0)
            FROM producto pr
            LEFT JOIN inventario_stock st ON st.cod_pro=pr.cod_pro
            WHERE pr.estado='ACTIVO'
            GROUP BY pr.cod_pro,pr.des_pro
            HAVING COALESCE(SUM(st.cantidad),0) > 0
            ORDER BY pr.des_pro
            """, valores("viaje", viaje, "inicio", v.get("fechaInicio"), "fin", v.get("fechaFin")),
            "codigo","nombre","stock","reservado");
    }

    private Map<String,Object> viajeBase(AccesoService.Identidad user, String codigo) {
        var v = db.primero("""
            SELECT v.cod_viaje,v.fecha_inicio,v.fecha_fin,per.email
            FROM viaje_confirmado v
            JOIN propuesta_viaje p ON p.cod_propuesta=v.cod_propuesta
            JOIN solicitud_viaje s ON s.cod_solicitud=p.cod_solicitud
            JOIN cliente c ON c.cod_cliente=s.cod_cliente
            JOIN persona per ON per.cod_persona=c.cod_persona
            WHERE v.cod_viaje=:codigo AND v.estado='CONFIRMADO'
              AND (:admin=1 OR EXISTS(SELECT 1 FROM usuario u JOIN asignacion_asesor a ON a.id_usuario_cli=u.id_usuario
                  WHERE u.email=per.email AND a.activa=1 AND a.id_usuario_ase=:usuario))
            """, Map.of("codigo", codigo, "admin", admin(user) ? 1 : 0, "usuario", user.id()),
            "codigo","fechaInicio","fechaFin","email");
        if (v.isEmpty()) throw new NotFoundException("Viaje confirmado no encontrado.");
        return v;
    }

    @Transactional
    public Map<String,Object> asignarPersonal(AccesoService.Identidad user, String viaje, PersonalAsignacion datos) {
        viajeBase(user, viaje);
        if (datos == null) throw new BadRequestException("Selecciona el personal.");
        String empleado = AccesoService.texto(datos.empleado(), "el empleado", 20);
        String rol = AccesoService.texto(datos.rol(), "el rol", 30).toUpperCase(Locale.ROOT);
        if (!Set.of("GUIA", "CONDUCTOR", "COCINERO", "OTRO").contains(rol)) {
            throw new BadRequestException("El rol debe ser guía, conductor, cocinero u otro.");
        }
        var emp = db.primero("SELECT cod_empleado,es_guia,es_conductor,es_cocinero,estado FROM empleado WHERE cod_empleado=:codigo",
            Map.of("codigo", empleado), "codigo","guia","conductor","cocinero","estado");
        if (emp.isEmpty() || !"ACTIVO".equals(emp.get("estado"))) throw new NotFoundException("Empleado no encontrado.");
        if ("GUIA".equals(rol) && !Integer.valueOf(1).equals(toInt(emp.get("guia")))) throw new BadRequestException("Ese empleado no está marcado como guía.");
        if ("CONDUCTOR".equals(rol) && !Integer.valueOf(1).equals(toInt(emp.get("conductor")))) throw new BadRequestException("Ese empleado no está marcado como conductor.");
        if ("COCINERO".equals(rol) && !Integer.valueOf(1).equals(toInt(emp.get("cocinero")))) throw new BadRequestException("Ese empleado no está marcado como cocinero.");
        if (!db.lista("SELECT id_asignacion FROM viaje_personal WHERE cod_viaje=:viaje AND cod_empleado=:empleado", Map.of("viaje", viaje, "empleado", empleado), "id").isEmpty()) {
            throw new ClientErrorException("Ese empleado ya está asignado a este viaje.", 409);
        }
        String notas = datos.notas() == null || datos.notas().isBlank() ? null : AccesoService.texto(datos.notas(), "las notas", 500);
        db.ejecutar("INSERT INTO viaje_personal (cod_viaje,cod_empleado,rol_en_viaje,notas) VALUES (:viaje,:empleado,:rol,:notas)",
            valores("viaje", viaje, "empleado", empleado, "rol", rol, "notas", notas));
        return Map.of("mensaje", "Personal asignado al viaje.");
    }

    @Transactional
    public void quitarPersonal(AccesoService.Identidad user, String viaje, long id) {
        viajeBase(user, viaje);
        int n = db.ejecutar("DELETE FROM viaje_personal WHERE id_asignacion=:id AND cod_viaje=:viaje", Map.of("id", id, "viaje", viaje));
        if (n == 0) throw new NotFoundException("Asignación no encontrada.");
    }

    @Transactional
    public Map<String,Object> asignarUtileria(AccesoService.Identidad user, String viaje, UtileriaAsignacion datos) {
        var v = viajeBase(user, viaje);
        if (datos == null) throw new BadRequestException("Selecciona la utilería.");
        String producto = AccesoService.texto(datos.producto(), "el producto", 30);
        if (datos.cantidad() == null || datos.cantidad() < 1 || datos.cantidad() > 100000) {
            throw new BadRequestException("Indica una cantidad válida.");
        }
        var stock = db.primero("""
            SELECT COALESCE(SUM(st.cantidad),0),
              COALESCE((SELECT SUM(vu.cantidad) FROM viaje_utileria vu JOIN viaje_confirmado vc ON vc.cod_viaje=vu.cod_viaje
                WHERE vu.cod_producto=:producto AND vc.estado='CONFIRMADO'
                  AND vc.fecha_inicio<=:fin AND vc.fecha_fin>=:inicio AND vc.cod_viaje<>:viaje),0)
            FROM producto pr LEFT JOIN inventario_stock st ON st.cod_pro=pr.cod_pro
            WHERE pr.cod_pro=:producto AND pr.estado='ACTIVO'
            """, valores("producto", producto, "viaje", viaje, "inicio", v.get("fechaInicio"), "fin", v.get("fechaFin")), "stock", "reservado");
        if (stock.isEmpty()) throw new NotFoundException("Producto no encontrado.");
        BigDecimal disponible = new BigDecimal(stock.get("stock").toString()).subtract(new BigDecimal(stock.get("reservado").toString()));
        var actual = db.primero("SELECT cantidad FROM viaje_utileria WHERE cod_viaje=:viaje AND cod_producto=:producto",
            Map.of("viaje", viaje, "producto", producto), "cantidad");
        int ya = actual.isEmpty() ? 0 : toInt(actual.get("cantidad"));
        if (disponible.subtract(BigDecimal.valueOf(ya)).compareTo(BigDecimal.valueOf(datos.cantidad())) < 0) {
            throw new BadRequestException("No hay suficiente utilería disponible para esas fechas (disponible: " + disponible.subtract(BigDecimal.valueOf(ya)).max(BigDecimal.ZERO).intValue() + ").");
        }
        String notas = datos.notas() == null || datos.notas().isBlank() ? null : AccesoService.texto(datos.notas(), "las notas", 500);
        if (actual.isEmpty()) {
            db.ejecutar("INSERT INTO viaje_utileria (cod_viaje,cod_producto,cantidad,notas) VALUES (:viaje,:producto,:cantidad,:notas)",
                valores("viaje", viaje, "producto", producto, "cantidad", datos.cantidad(), "notas", notas));
        } else {
            db.ejecutar("UPDATE viaje_utileria SET cantidad=:cantidad, notas=COALESCE(:notas,notas) WHERE cod_viaje=:viaje AND cod_producto=:producto",
                valores("viaje", viaje, "producto", producto, "cantidad", datos.cantidad(), "notas", notas));
        }
        return Map.of("mensaje", "Utilería reservada para el viaje.");
    }

    @Transactional
    public void quitarUtileria(AccesoService.Identidad user, String viaje, long id) {
        viajeBase(user, viaje);
        int n = db.ejecutar("DELETE FROM viaje_utileria WHERE id_utileria=:id AND cod_viaje=:viaje", Map.of("id", id, "viaje", viaje));
        if (n == 0) throw new NotFoundException("Utilería no encontrada.");
    }

    @Transactional
    public Map<String,Object> pagarCliente(AccesoService.Identidad user, String reserva, PagoCliente datos) {
        if (!"CLIENTE".equals(user.rol())) throw new ForbiddenException();
        var c = portal.cliente(user, true);
        db.lista("SELECT cod_reserva FROM reserva WHERE cod_reserva=:codigo FOR UPDATE", Map.of("codigo", reserva), "codigo");
        var r = db.primero("""
            SELECT r.cod_reserva,r.estado,r.total,r.moneda,r.cod_cliente,
              COALESCE((SELECT SUM(monto) FROM pago WHERE cod_reserva=r.cod_reserva AND estado='CONFIRMADO'),0)
            FROM reserva r JOIN viaje_confirmado v ON v.cod_reserva=r.cod_reserva
            WHERE r.cod_reserva=:codigo AND r.cod_cliente=:cliente
            """, Map.of("codigo", reserva, "cliente", c.get("codigo")), "codigo","estado","total","moneda","cliente","pagado");
        if (r.isEmpty()) throw new NotFoundException("Reserva no encontrada.");
        if (!Set.of("PENDIENTE_ANTICIPO", "CONFIRMADA").contains((String) r.get("estado"))) {
            throw new ClientErrorException("Esta reserva no admite pagos en línea.", 409);
        }
        if (datos == null || datos.tipo() == null) throw new BadRequestException("Elige anticipo o pago total.");
        String tipo = datos.tipo().trim().toUpperCase(Locale.ROOT);
        if (!Set.of("ANTICIPO", "TOTAL").contains(tipo)) throw new BadRequestException("Elige anticipo o pago total.");
        AccesoService.texto(datos.titular(), "el titular de la tarjeta", 120);
        AccesoService.texto(datos.metodo() == null || datos.metodo().isBlank() ? "Pasarela Thaki" : datos.metodo(), "el método", 50);
        BigDecimal total = new BigDecimal(r.get("total").toString());
        BigDecimal pagado = new BigDecimal(r.get("pagado").toString());
        BigDecimal saldo = total.subtract(pagado);
        if (saldo.signum() <= 0) throw new ClientErrorException("Esta reserva ya está pagada.", 409);
        BigDecimal anticipo = total.multiply(new BigDecimal("0.5")).setScale(2, java.math.RoundingMode.CEILING);
        BigDecimal monto = "TOTAL".equals(tipo) ? saldo : anticipo.subtract(pagado).max(BigDecimal.ZERO);
        if (monto.signum() <= 0) {
            if ("ANTICIPO".equals(tipo) && pagado.compareTo(anticipo) >= 0) {
                throw new ClientErrorException("El anticipo ya está cubierto. Puedes pagar el saldo restante.", 409);
            }
            throw new BadRequestException("No hay monto pendiente para ese tipo de pago.");
        }
        if (monto.compareTo(saldo) > 0) monto = saldo;
        String referencia = AccesoService.texto(
            datos.referencia() == null || datos.referencia().isBlank()
                ? "ONLINE-" + GestionValidationService.codigo("PAG")
                : datos.referencia(),
            "la referencia", 100);
        if (!db.lista("SELECT cod_pago FROM pago WHERE cod_reserva=:codigo AND referencia=:referencia AND estado='CONFIRMADO'",
            Map.of("codigo", reserva, "referencia", referencia), "codigo").isEmpty()) {
            throw new ClientErrorException("Esta referencia ya está registrada.", 409);
        }
        String pago = GestionValidationService.codigo("PAG");
        db.ejecutar("""
            INSERT INTO pago (cod_pago,cod_reserva,fecha,monto,metodo,referencia,estado)
            VALUES (:pago,:reserva,CURRENT_DATE,:monto,:metodo,:referencia,'CONFIRMADO')
            """, Map.of("pago", pago, "reserva", reserva, "monto", monto,
            "metodo", "Pasarela en línea · " + tipo, "referencia", referencia));
        if ("PENDIENTE_ANTICIPO".equals(r.get("estado")) && pagado.add(monto).compareTo(anticipo) >= 0) {
            db.ejecutar("UPDATE reserva SET estado='CONFIRMADA' WHERE cod_reserva=:codigo", Map.of("codigo", reserva));
            db.ejecutar("UPDATE viaje_confirmado SET estado='CONFIRMADO' WHERE cod_reserva=:codigo", Map.of("codigo", reserva));
            portal.notificar(user.id(), "Viaje confirmado", "Tu pago habilitó la confirmación del viaje. Reserva " + reserva + ".");
        } else {
            portal.notificar(user.id(), "Pago registrado", "Registramos tu pago en línea para la reserva " + reserva + ".");
        }
        return Map.of("codigo", pago, "monto", monto, "estado", "CONFIRMADO");
    }

    private static Integer toInt(Object value) {
        if (value instanceof Number n) return n.intValue();
        if (value == null) return 0;
        try { return Integer.parseInt(value.toString()); } catch (NumberFormatException e) { return 0; }
    }
}
