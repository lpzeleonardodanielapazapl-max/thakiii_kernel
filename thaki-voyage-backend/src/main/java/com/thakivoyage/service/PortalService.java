package com.thakivoyage.service;

import static com.thakivoyage.service.PortalQuery.valores;
import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.*;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.*;

@ApplicationScoped
public class PortalService {
    @Inject PortalQuery db;
    @Inject GestionValidationService validacion;
    public record Texto(String contenido) {}
    public record Viaje(LocalDate fechaInicio, LocalDate fechaFin, Integer personas, BigDecimal presupuesto,
        String moneda, String destinos, String intereses, String hotel, String alimentacion, String transporte) {}

    private Map<String, Object> persona(AccesoService.Identidad user) {
        var personas = db.lista("""
            SELECT cod_persona, nombres, apellidos, email, telefono, direccion, tipo_documento,
                   numero_documento, fecha_nacimiento, genero
            FROM persona WHERE email=:email
            """, Map.of("email", user.email()), "codigo", "nombres", "apellidos", "email", "telefono", "direccion", "tipoDocumento", "numeroDocumento", "fechaNacimiento", "genero");
        if (personas.size() > 1) throw new ClientErrorException("Hay más de un registro personal con tu correo. Solicita al personal revisar tu perfil.", 409);
        var perfil = new LinkedHashMap<String, Object>(personas.isEmpty() ? Map.of("email", user.email()) : personas.getFirst());
        perfil.put("completitud", completitud(perfil));
        return perfil;
    }

    static Map<String, Object> completitud(Map<String, Object> perfil) {
        var campos = valores("nombres", "Nombres", "apellidos", "Apellidos", "email", "Correo",
            "telefono", "Teléfono", "direccion", "Dirección", "tipoDocumento", "Tipo de documento",
            "numeroDocumento", "Número de documento", "fechaNacimiento", "Fecha de nacimiento");
        var faltantes = new ArrayList<String>();
        var claves = new ArrayList<String>();
        campos.forEach((campo, etiqueta) -> {
            Object valor = perfil.get(campo);
            boolean valido = valor != null && !valor.toString().isBlank();
            if (valido && campo.equals("fechaNacimiento")) {
                try { valido = !LocalDate.parse(valor.toString()).isAfter(LocalDate.now()); }
                catch (java.time.format.DateTimeParseException e) { valido = false; }
            }
            if (!valido) { faltantes.add(etiqueta.toString()); claves.add(campo); }
        });
        return valores("completo", faltantes.isEmpty(), "porcentaje", (campos.size() - faltantes.size()) * 100 / campos.size(),
            "faltantes", faltantes, "camposFaltantes", claves);
    }

    private void exigirPerfilCompleto(AccesoService.Identidad user) {
        var estado = completitud(persona(user));
        if (!Boolean.TRUE.equals(estado.get("completo"))) {
            throw new ClientErrorException("Completa tu perfil al 100 % antes de enviar tu solicitud. Faltan: "
                + ((List<?>) estado.get("faltantes")).stream().map(Object::toString).collect(java.util.stream.Collectors.joining(", ")) + ".", 409);
        }
    }

    private Map<String, Object> cliente(AccesoService.Identidad user, boolean obligatorio) {
        var persona = persona(user);
        if (!persona.containsKey("codigo")) {
            if (obligatorio) throw new ClientErrorException("Completa tu perfil antes de solicitar un viaje.", 409);
            return Map.of();
        }
        var clientes = db.lista("SELECT cod_cliente, estado FROM cliente WHERE cod_persona=:persona", Map.of("persona", persona.get("codigo")), "codigo", "estado");
        if (clientes.size() > 1) throw new ClientErrorException("Solicita al personal revisar la vinculación de tu perfil.", 409);
        if (clientes.isEmpty()) {
            if (obligatorio) throw new ClientErrorException("Completa tu perfil antes de solicitar un viaje.", 409);
            return Map.of();
        }
        if (obligatorio && !"ACTIVO".equals(clientes.getFirst().get("estado"))) throw new ForbiddenException("Tu ficha de cliente está inactiva. Contacta al personal.");
        return clientes.getFirst();
    }

    public Map<String, Object> perfil(AccesoService.Identidad user) { return persona(user); }

    @Transactional
    public Map<String, Object> guardarPerfil(AccesoService.Identidad user, com.thakivoyage.dto.request.PersonaRequest datos) {
        var actual = persona(user);
        datos.email = user.email();
        String codigo = (String) actual.get("codigo");
        validacion.validar(datos, codigo);
        if (codigo == null) {
            codigo = GestionValidationService.codigo("PER");
            db.ejecutar("INSERT INTO persona (cod_persona,nombres,apellidos,email) VALUES (:codigo,:nombres,:apellidos,:email)", valores("codigo", codigo, "nombres", datos.nombres, "apellidos", datos.apellidos, "email", user.email()));
        }
        db.ejecutar("""
            UPDATE persona SET nombres=:nombres, apellidos=:apellidos, telefono=:telefono, direccion=:direccion,
                tipo_documento=:tipo, numero_documento=:documento, fecha_nacimiento=:nacimiento, genero=:genero
            WHERE cod_persona=:codigo
            """, valores("codigo", codigo, "nombres", datos.nombres, "apellidos", datos.apellidos, "telefono", datos.telefono,
            "direccion", datos.direccion, "tipo", datos.tipoDocumento, "documento", datos.numeroDocumento, "nacimiento", datos.fechaNacimiento, "genero", datos.genero));
        var existentes = db.lista("SELECT cod_cliente FROM cliente WHERE cod_persona=:persona", Map.of("persona", codigo), "codigo");
        if (existentes.isEmpty()) db.ejecutar("INSERT INTO cliente (cod_cliente,cod_persona,estado) VALUES (:codigo,:persona,'ACTIVO')", Map.of("codigo", GestionValidationService.codigo("CLI"), "persona", codigo));
        return persona(user);
    }

    public Map<String, Object> asesor(AccesoService.Identidad user) {
        return db.primero("""
            SELECT u.id_usuario, COALESCE(NULLIF(TRIM(CONCAT(p.nombres,' ',p.apellidos)),''),u.username,u.email), u.email
            FROM asignacion_asesor a JOIN usuario u ON u.id_usuario=a.id_usuario_ase
            JOIN rol r ON r.cod_rol=u.cod_rol
            JOIN persona p ON p.email=u.email JOIN empleado e ON e.cod_persona=p.cod_persona
            WHERE a.id_usuario_cli=:usuario AND a.activa=1 AND u.activo=1
              AND r.des_rol='ADMINISTRATIVO' AND e.estado='ACTIVO' AND e.es_asesor=1
            ORDER BY a.cod_asignacion DESC LIMIT 1
            """, Map.of("usuario", user.id()), "id", "nombre", "email");
    }

    @Transactional
    public Map<String, Object> asignar(AccesoService.Identidad user) {
        db.lista("SELECT cod_rol FROM rol WHERE des_rol='ADMINISTRATIVO' FOR UPDATE", Map.of(), "codigo");
        var actual = asesor(user);
        if (!actual.isEmpty()) return actual;
        var siguiente = db.primero("""
            SELECT u.id_usuario, COALESCE(u.username,CONCAT(p.nombres,' ',p.apellidos))
            FROM usuario u JOIN rol r ON r.cod_rol=u.cod_rol
            JOIN persona p ON p.email=u.email JOIN empleado e ON e.cod_persona=p.cod_persona
            LEFT JOIN asignacion_asesor a ON a.id_usuario_ase=u.id_usuario
            WHERE u.activo=1 AND r.des_rol='ADMINISTRATIVO' AND e.estado='ACTIVO' AND e.es_asesor=1
            GROUP BY u.id_usuario,u.username,p.nombres,p.apellidos
            ORDER BY COALESCE(MAX(a.cod_asignacion),0),u.id_usuario LIMIT 1
            """, Map.of(), "id", "nombre");
        if (siguiente.isEmpty()) return Map.of();
        db.ejecutar("UPDATE asignacion_asesor SET activa=0 WHERE id_usuario_cli=:usuario", Map.of("usuario", user.id()));
        db.ejecutar("INSERT INTO asignacion_asesor (id_usuario_cli,id_usuario_ase,activa) VALUES (:cliente,:asesor,1)", Map.of("cliente", user.id(), "asesor", siguiente.get("id")));
        db.ejecutar("INSERT INTO conversacion (id_usuario_cliente,id_usuario_asesor,asunto,estado) VALUES (:cliente,:asesor,'Tu viaje a medida','ABIERTA')", Map.of("cliente", user.id(), "asesor", siguiente.get("id")));
        notificar(user.id(), "Tu asesor está asignado", "Ya puedes conversar con tu asesor desde Mi viaje.");
        return asesor(user);
    }

    public Map<String, Object> resumen(AccesoService.Identidad user) {
        var c = cliente(user, false);
        Object codigo = c.getOrDefault("codigo", "");
        var solicitudes = db.lista("SELECT cod_solicitud,fecha_inicio,fecha_fin,num_personas,presupuesto,moneda,intereses,estado FROM solicitud_viaje WHERE cod_cliente=:cliente ORDER BY fecha_solicitud DESC LIMIT 20", Map.of("cliente", codigo), "codigo", "fechaInicio", "fechaFin", "personas", "presupuesto", "moneda", "intereses", "estado");
        var propuestas = db.lista("""
            SELECT p.cod_propuesta,p.titulo,p.descripcion,p.duracion_dias,p.precio_total,p.moneda,p.estado,p.version,s.fecha_inicio,s.fecha_fin
            FROM propuesta_viaje p JOIN solicitud_viaje s ON s.cod_solicitud=p.cod_solicitud
            WHERE s.cod_cliente=:cliente ORDER BY p.fecha_envio DESC LIMIT 20
            """, Map.of("cliente", codigo), "codigo", "titulo", "descripcion", "dias", "precio", "moneda", "estado", "version", "fechaInicio", "fechaFin");
        var reservas = db.lista("""
            SELECT r.cod_reserva,r.fecha_inicio,r.fecha_fin,r.num_personas,r.estado,r.total,r.moneda,
                COALESCE(SUM(CASE WHEN p.estado='CONFIRMADO' THEN p.monto ELSE 0 END),0)
            FROM reserva r LEFT JOIN pago p ON p.cod_reserva=r.cod_reserva
            WHERE r.cod_cliente=:cliente
            GROUP BY r.cod_reserva,r.fecha_inicio,r.fecha_fin,r.num_personas,r.estado,r.total,r.moneda
            ORDER BY r.fecha_reserva DESC
            """, Map.of("cliente", codigo), "codigo", "fechaInicio", "fechaFin", "personas", "estado", "total", "moneda", "pagado");
        for (var reserva : reservas) {
            BigDecimal total = new BigDecimal(Objects.toString(reserva.get("total"), "0"));
            BigDecimal pagado = new BigDecimal(reserva.get("pagado").toString());
            reserva.put("anticipo", total.multiply(new BigDecimal("0.5")).setScale(2, java.math.RoundingMode.CEILING));
            reserva.put("saldo", total.subtract(pagado).max(BigDecimal.ZERO));
        }
        return valores("perfil", persona(user), "cliente", c, "asesor", asesor(user), "solicitudes", solicitudes,
            "propuestas", propuestas, "reservas", reservas, "notificaciones", notificaciones(user));
    }

    public Map<String, Object> propuesta(AccesoService.Identidad user, String codigo) {
        var c = cliente(user, true);
        var p = db.primero("""
            SELECT p.cod_propuesta,p.titulo,p.descripcion,p.duracion_dias,p.precio_total,p.moneda,p.estado,p.version
            FROM propuesta_viaje p JOIN solicitud_viaje s ON s.cod_solicitud=p.cod_solicitud
            WHERE p.cod_propuesta=:codigo AND s.cod_cliente=:cliente
            """, Map.of("codigo", codigo, "cliente", c.get("codigo")), "codigo", "titulo", "descripcion", "dias", "precio", "moneda", "estado", "version");
        if (p.isEmpty()) throw new NotFoundException("Propuesta no encontrada.");
        p.put("itinerario", db.lista("SELECT dia,descripcion,servicios FROM propuesta_detalle WHERE cod_propuesta=:codigo ORDER BY dia,id_detalle", Map.of("codigo", codigo), "dia", "descripcion", "servicios"));
        return p;
    }

    @Transactional
    public Map<String, Object> solicitar(AccesoService.Identidad user, Viaje datos) {
        exigirPerfilCompleto(user);
        var c = cliente(user, true);
        if (datos == null || datos.fechaInicio()==null || datos.fechaFin()==null || datos.fechaInicio().isBefore(LocalDate.now()) || datos.fechaFin().isBefore(datos.fechaInicio())) throw new BadRequestException("Selecciona fechas válidas para tu viaje.");
        if (datos.personas()==null || datos.personas()<1 || datos.personas()>1000) throw new BadRequestException("Indica la cantidad de viajeros, entre 1 y 1000.");
        if (datos.presupuesto()!=null && (datos.presupuesto().signum()<0 || datos.presupuesto().compareTo(new BigDecimal("9999999999.99"))>0 || datos.presupuesto().scale()>2)) throw new BadRequestException("Revisa el presupuesto.");
        if (!Set.of("BOB","USD","EUR").contains(datos.moneda()==null?"":datos.moneda())) throw new BadRequestException("Selecciona la moneda del presupuesto.");
        String intereses = AccesoService.texto(datos.intereses(), "tus intereses", 4000);
        String destinos = datos.destinos()==null?"":datos.destinos().strip();
        if (destinos.length()>200) throw new BadRequestException("Indica hasta 200 caracteres para los destinos.");
        String codigo = GestionValidationService.codigo("SOL");
        db.ejecutar("""
            INSERT INTO solicitud_viaje (cod_solicitud,cod_cliente,fecha_inicio,fecha_fin,num_personas,presupuesto,moneda,intereses,estado)
            VALUES (:codigo,:cliente,:inicio,:fin,:personas,:presupuesto,:moneda,:intereses,'RECIBIDA')
            """, valores("codigo",codigo,"cliente",c.get("codigo"),"inicio",datos.fechaInicio(),"fin",datos.fechaFin(),"personas",datos.personas(),"presupuesto",datos.presupuesto(),"moneda",datos.moneda(),"intereses",(destinos.isBlank()?"":"Destinos: "+destinos+"\n")+intereses));
        preferencias(codigo,"HOTEL",datos.hotel()); preferencias(codigo,"ALIMENTACION",datos.alimentacion()); preferencias(codigo,"TRANSPORTE",datos.transporte());
        var asesor = asignar(user);
        if (!asesor.isEmpty()) notificar(((Number)asesor.get("id")).longValue(),"Nueva solicitud de viaje", "El cliente envió la solicitud "+codigo+".");
        return Map.of("codigo",codigo,"estado","RECIBIDA");
    }

    private void preferencias(String solicitud, String tipo, String value) {
        if (value==null || value.isBlank()) return;
        String texto=AccesoService.texto(value,"la preferencia de "+tipo.toLowerCase(),200);
        db.ejecutar("INSERT INTO solicitud_preferencia (cod_solicitud,tipo,valor) VALUES (:codigo,:tipo,:valor)",Map.of("codigo",solicitud,"tipo",tipo,"valor",texto));
    }

    public List<Map<String,Object>> mensajes(AccesoService.Identidad user) {
        return db.lista("""
            SELECT m.cod_mensaje,m.id_remitente,m.contenido,m.fecha_envio
            FROM mensaje m JOIN conversacion c ON c.cod_conversacion=m.cod_conversacion
            WHERE c.id_usuario_cliente=:usuario ORDER BY m.fecha_envio DESC,m.cod_mensaje DESC LIMIT 100
            """,Map.of("usuario",user.id()),"id","remitente","contenido","fecha");
    }

    @Transactional
    public void enviar(AccesoService.Identidad user, Texto datos) {
        if (datos==null) throw new BadRequestException("Escribe un mensaje.");
        String texto=AccesoService.texto(datos.contenido(),"el mensaje",4000);
        var asesor=asignar(user);
        if (asesor.isEmpty()) throw new ClientErrorException("Todavía no hay un asesor disponible. Tu solicitud puede registrarse y será atendida por el personal.",409);
        var conversacion=db.primero("SELECT cod_conversacion FROM conversacion WHERE id_usuario_cliente=:cliente AND id_usuario_asesor=:asesor AND estado='ABIERTA' ORDER BY cod_conversacion DESC LIMIT 1",Map.of("cliente",user.id(),"asesor",asesor.get("id")),"codigo");
        if (conversacion.isEmpty()) throw new ClientErrorException("No hay una conversación activa. Solicita al personal revisar tu asignación.",409);
        insertarMensaje(((Number)conversacion.get("codigo")).longValue(),user.id(),texto);
        notificar(((Number)asesor.get("id")).longValue(),"Nuevo mensaje de cliente", "Tienes un nuevo mensaje en Atención a clientes.");
    }

    public void insertarMensaje(long conversacion,long remitente,String texto) {
        db.ejecutar("INSERT INTO mensaje (cod_conversacion,id_remitente,contenido,tipo) VALUES (:conversacion,:usuario,:texto,'TEXTO')",Map.of("conversacion",conversacion,"usuario",remitente,"texto",texto));
        db.ejecutar("UPDATE conversacion SET fecha_ultimo_msg=CURRENT_TIMESTAMP WHERE cod_conversacion=:codigo",Map.of("codigo",conversacion));
    }

    @Transactional
    public void aceptar(AccesoService.Identidad user,String codigo) {
        var p=propuesta(user,codigo);
        if (!"ENVIADA".equals(p.get("estado"))) throw new ClientErrorException("Esta propuesta ya no está pendiente de respuesta.",409);
        enviar(user,new Texto("Quiero aceptar la propuesta "+codigo+". Solicito al administrativo registrar mi aceptación y orientarme sobre el anticipo del 50 %."));
    }

    @Transactional
    public void cancelar(AccesoService.Identidad user,String reserva,Texto datos) {
        var c=cliente(user,true);
        if (db.lista("SELECT cod_reserva FROM reserva WHERE cod_reserva=:codigo AND cod_cliente=:cliente",Map.of("codigo",reserva,"cliente",c.get("codigo")),"codigo").isEmpty()) throw new NotFoundException("Reserva no encontrada.");
        if (datos==null) throw new BadRequestException("Indica el motivo de tu solicitud.");
        enviar(user,new Texto("[SOLICITUD DE CANCELACIÓN] Reserva "+reserva+". Motivo: "+AccesoService.texto(datos.contenido(),"el motivo",2000)));
    }

    public List<Map<String,Object>> notificaciones(AccesoService.Identidad user) {
        return db.lista("SELECT cod_notificacion,titulo,contenido,leida,fecha FROM notificacion WHERE id_usuario=:usuario ORDER BY fecha DESC,cod_notificacion DESC LIMIT 50",Map.of("usuario",user.id()),"codigo","titulo","contenido","leida","fecha");
    }

    @Transactional
    public void leer(AccesoService.Identidad user,long id) {
        if (db.lista("SELECT cod_notificacion FROM notificacion WHERE cod_notificacion=:id AND id_usuario=:usuario",Map.of("id",id,"usuario",user.id()),"id").isEmpty()) throw new NotFoundException("Notificación no encontrada.");
        db.ejecutar("UPDATE notificacion SET leida=1 WHERE cod_notificacion=:id AND id_usuario=:usuario",Map.of("id",id,"usuario",user.id()));
    }

    public void notificar(long usuario,String titulo,String contenido) {
        db.ejecutar("INSERT INTO notificacion (id_usuario,titulo,contenido,tipo,leida) VALUES (:usuario,:titulo,:contenido,'INTERNA',0)",Map.of("usuario",usuario,"titulo",titulo,"contenido",contenido));
    }
}
