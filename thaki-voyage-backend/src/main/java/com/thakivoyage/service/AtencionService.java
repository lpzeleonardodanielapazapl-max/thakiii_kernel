package com.thakivoyage.service;

import static com.thakivoyage.service.PortalQuery.valores;
import java.math.BigDecimal;
import java.util.*;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.*;

@ApplicationScoped
public class AtencionService {
    @Inject PortalQuery db;
    @Inject PortalService portal;
    public record Dia(Integer dia,String descripcion,String servicios) {}
    public record Propuesta(String solicitud,String titulo,String descripcion,BigDecimal precio,String moneda,List<Dia> dias) {}
    public record Pago(java.time.LocalDate fecha,BigDecimal monto,String metodo,String referencia,Boolean verificado) {}

    private boolean admin(AccesoService.Identidad user) { return user.rol().equals("ADMIN"); }

    public Map<String,Object> panel(AccesoService.Identidad user) {
        return valores("conversaciones",db.lista("""
            SELECT c.cod_conversacion,c.id_usuario_cliente,COALESCE(u.username,u.email),u.email,c.asunto,c.fecha_ultimo_msg,
                COALESCE(a.username,a.email)
            FROM conversacion c JOIN usuario u ON u.id_usuario=c.id_usuario_cliente
            LEFT JOIN usuario a ON a.id_usuario=c.id_usuario_asesor
            WHERE (:admin=1 OR c.id_usuario_asesor=:usuario) ORDER BY COALESCE(c.fecha_ultimo_msg,c.fecha_inicio) DESC LIMIT 100
            """,Map.of("admin",admin(user)?1:0,"usuario",user.id()),"codigo","clienteId","cliente","email","asunto","fecha","asesor"),
            "solicitudes",db.lista("""
            SELECT s.cod_solicitud,s.cod_cliente,p.nombres,p.apellidos,s.fecha_inicio,s.fecha_fin,s.num_personas,s.presupuesto,s.moneda,s.intereses,s.estado
            FROM solicitud_viaje s JOIN cliente c ON c.cod_cliente=s.cod_cliente JOIN persona p ON p.cod_persona=c.cod_persona
            WHERE (:admin=1 OR EXISTS(SELECT 1 FROM usuario u JOIN asignacion_asesor a ON a.id_usuario_cli=u.id_usuario
                WHERE u.email=p.email AND a.activa=1 AND a.id_usuario_ase=:usuario))
            ORDER BY s.fecha_solicitud DESC LIMIT 100
            """,Map.of("admin",admin(user)?1:0,"usuario",user.id()),"codigo","clienteCodigo","nombres","apellidos","fechaInicio","fechaFin","personas","presupuesto","moneda","intereses","estado"));
    }

    public Map<String,Object> conversacion(AccesoService.Identidad user,long id) {
        var c=db.primero("SELECT cod_conversacion,id_usuario_cliente,id_usuario_asesor,asunto FROM conversacion WHERE cod_conversacion=:id AND (:admin=1 OR id_usuario_asesor=:usuario)",Map.of("id",id,"admin",admin(user)?1:0,"usuario",user.id()),"codigo","clienteId","asesorId","asunto");
        if(c.isEmpty())throw new NotFoundException("Conversación no encontrada.");
        c.put("mensajes",db.lista("SELECT cod_mensaje,id_remitente,contenido,fecha_envio FROM mensaje WHERE cod_conversacion=:id ORDER BY fecha_envio DESC,cod_mensaje DESC LIMIT 100",Map.of("id",id),"id","remitente","contenido","fecha"));
        return c;
    }

    @Transactional
    public void responder(AccesoService.Identidad user,long id,PortalService.Texto datos) {
        var c=conversacion(user,id);
        if(datos==null)throw new BadRequestException("Escribe un mensaje.");
        portal.insertarMensaje(id,user.id(),AccesoService.texto(datos.contenido(),"el mensaje",4000));
        portal.notificar(((Number)c.get("clienteId")).longValue(),"Tu asesor te escribió","Tienes un nuevo mensaje en tu espacio de viaje.");
    }

    public Map<String,Object> solicitud(AccesoService.Identidad user,String codigo) {
        var s=db.primero("""
            SELECT s.cod_solicitud,s.cod_cliente,p.nombres,p.apellidos,p.email,s.fecha_inicio,s.fecha_fin,s.num_personas,s.presupuesto,s.moneda,s.intereses,s.estado,u.id_usuario
            FROM solicitud_viaje s JOIN cliente c ON c.cod_cliente=s.cod_cliente JOIN persona p ON p.cod_persona=c.cod_persona
            JOIN usuario u ON u.email=p.email
            WHERE s.cod_solicitud=:codigo AND (:admin=1 OR EXISTS(SELECT 1 FROM asignacion_asesor a
                WHERE a.id_usuario_cli=u.id_usuario AND a.activa=1 AND a.id_usuario_ase=:usuario))
            """,Map.of("codigo",codigo,"admin",admin(user)?1:0,"usuario",user.id()),"codigo","clienteCodigo","nombres","apellidos","email","fechaInicio","fechaFin","personas","presupuesto","moneda","intereses","estado","clienteId");
        if(s.isEmpty())throw new NotFoundException("Solicitud no encontrada.");
        s.put("preferencias",db.lista("SELECT tipo,valor FROM solicitud_preferencia WHERE cod_solicitud=:codigo ORDER BY id_preferencia",Map.of("codigo",codigo),"tipo","valor"));
        s.put("propuestas",db.lista("SELECT cod_propuesta,titulo,precio_total,moneda,estado,version FROM propuesta_viaje WHERE cod_solicitud=:codigo ORDER BY version DESC",Map.of("codigo",codigo),"codigo","titulo","precio","moneda","estado","version"));
        s.put("reservas",db.lista("SELECT r.cod_reserva,r.estado,r.total,r.moneda FROM reserva r JOIN viaje_confirmado v ON v.cod_reserva=r.cod_reserva JOIN propuesta_viaje p ON p.cod_propuesta=v.cod_propuesta WHERE p.cod_solicitud=:codigo",Map.of("codigo",codigo),"codigo","estado","total","moneda"));
        return s;
    }

    @Transactional
    public Map<String,String> proponer(AccesoService.Identidad user,Propuesta datos) {
        if(datos==null)throw new BadRequestException("Completa la propuesta.");
        var s=solicitud(user,AccesoService.texto(datos.solicitud(),"la solicitud",20));
        String titulo=AccesoService.texto(datos.titulo(),"el título",200);
        String descripcion=AccesoService.texto(datos.descripcion(),"la descripción",4000);
        if(datos.precio()==null || datos.precio().signum()<=0 || datos.precio().scale()>2 || datos.precio().compareTo(new BigDecimal("9999999999.99"))>0)throw new BadRequestException("Introduce un precio válido.");
        if(!Set.of("BOB","USD","EUR").contains(datos.moneda()==null?"":datos.moneda()))throw new BadRequestException("Selecciona la moneda.");
        if(datos.dias()==null || datos.dias().isEmpty() || datos.dias().size()>120)throw new BadRequestException("Incluye entre 1 y 120 días en el itinerario.");
        db.lista("SELECT cod_solicitud FROM solicitud_viaje WHERE cod_solicitud=:codigo FOR UPDATE",Map.of("codigo",datos.solicitud()),"codigo");
        if (!db.lista("SELECT cod_propuesta FROM propuesta_viaje WHERE cod_solicitud=:codigo AND estado='ACEPTADA'",Map.of("codigo",datos.solicitud()),"codigo").isEmpty()) throw new ClientErrorException("La solicitud ya tiene una propuesta aceptada.",409);
        var version=db.primero("SELECT COALESCE(MAX(version),0)+1 FROM propuesta_viaje WHERE cod_solicitud=:codigo",Map.of("codigo",datos.solicitud()),"numero");
        String codigo=GestionValidationService.codigo("PRP");
        db.ejecutar("UPDATE propuesta_viaje SET estado='REEMPLAZADA' WHERE cod_solicitud=:codigo AND estado='ENVIADA'",Map.of("codigo",datos.solicitud()));
        db.ejecutar("INSERT INTO propuesta_viaje (cod_propuesta,cod_solicitud,version,titulo,descripcion,duracion_dias,precio_total,moneda,estado) VALUES (:codigo,:solicitud,:version,:titulo,:descripcion,:dias,:precio,:moneda,'ENVIADA')",valores("codigo",codigo,"solicitud",datos.solicitud(),"version",version.get("numero"),"titulo",titulo,"descripcion",descripcion,"dias",datos.dias().size(),"precio",datos.precio(),"moneda",datos.moneda()));
        for(int i=0;i<datos.dias().size();i++) {
            Dia dia=datos.dias().get(i);
            db.ejecutar("INSERT INTO propuesta_detalle (cod_propuesta,dia,descripcion,servicios) VALUES (:codigo,:dia,:descripcion,:servicios)",valores("codigo",codigo,"dia",i+1,"descripcion",AccesoService.texto(dia.descripcion(),"el detalle del día",4000),"servicios",dia.servicios()==null?null:AccesoService.texto(dia.servicios(),"los servicios",2000)));
        }
        portal.notificar(((Number)s.get("clienteId")).longValue(),"Tu propuesta está lista","Revisa la propuesta y su itinerario en Mi viaje.");
        return Map.of("codigo",codigo);
    }

    @Transactional
    public Map<String,String> aceptar(AccesoService.Identidad user,String propuesta) {
        var referencia=db.primero("SELECT cod_solicitud FROM propuesta_viaje WHERE cod_propuesta=:codigo",Map.of("codigo",propuesta),"solicitud");
        if(referencia.isEmpty())throw new NotFoundException("Propuesta no encontrada.");
        var s=solicitud(user,(String)referencia.get("solicitud"));
        db.lista("SELECT cod_solicitud FROM solicitud_viaje WHERE cod_solicitud=:codigo FOR UPDATE",Map.of("codigo",referencia.get("solicitud")),"codigo");
        var p=db.primero("SELECT cod_solicitud,precio_total,moneda,estado FROM propuesta_viaje WHERE cod_propuesta=:codigo FOR UPDATE",Map.of("codigo",propuesta),"solicitud","precio","moneda","estado");
        if (!db.lista("SELECT cod_propuesta FROM propuesta_viaje WHERE cod_solicitud=:codigo AND estado='ACEPTADA'",Map.of("codigo",p.get("solicitud")),"codigo").isEmpty()) throw new ClientErrorException("Ya se registró una propuesta aceptada para esta solicitud.",409);
        if(!"ENVIADA".equals(p.get("estado")))throw new ClientErrorException("La propuesta ya no está pendiente de aceptación.",409);
        String reserva=GestionValidationService.codigo("RES");
        db.ejecutar("INSERT INTO propuesta_aprobacion (cod_propuesta,aprobado,comentarios) VALUES (:codigo,1,:comentario)",Map.of("codigo",propuesta,"comentario","Aceptación del cliente registrada por el usuario "+user.id()));
        db.ejecutar("UPDATE propuesta_viaje SET estado='ACEPTADA' WHERE cod_propuesta=:codigo",Map.of("codigo",propuesta));
        db.ejecutar("INSERT INTO reserva (cod_reserva,cod_cliente,fecha_inicio,fecha_fin,num_personas,estado,total,moneda) VALUES (:codigo,:cliente,:inicio,:fin,:personas,'PENDIENTE_ANTICIPO',:total,:moneda)",valores("codigo",reserva,"cliente",s.get("clienteCodigo"),"inicio",s.get("fechaInicio"),"fin",s.get("fechaFin"),"personas",s.get("personas"),"total",p.get("precio"),"moneda",p.get("moneda")));
        db.ejecutar("INSERT INTO viaje_confirmado (cod_viaje,cod_propuesta,cod_reserva,fecha_inicio,fecha_fin,estado) VALUES (:codigo,:propuesta,:reserva,:inicio,:fin,'PENDIENTE_ANTICIPO')",valores("codigo",GestionValidationService.codigo("VIA"),"propuesta",propuesta,"reserva",reserva,"inicio",s.get("fechaInicio"),"fin",s.get("fechaFin")));
        portal.notificar(((Number)s.get("clienteId")).longValue(),"Aceptación registrada","El viaje queda pendiente del anticipo obligatorio del 50 %. Coordina el pago externo con tu asesor.");
        return Map.of("reserva",reserva);
    }

    public Map<String,Object> reserva(AccesoService.Identidad user,String codigo) {
        var r=db.primero("SELECT r.cod_reserva,p.cod_solicitud,r.estado,r.total,r.moneda,r.fecha_inicio,r.fecha_fin FROM reserva r JOIN viaje_confirmado v ON v.cod_reserva=r.cod_reserva JOIN propuesta_viaje p ON p.cod_propuesta=v.cod_propuesta WHERE r.cod_reserva=:codigo",Map.of("codigo",codigo),"codigo","solicitud","estado","total","moneda","fechaInicio","fechaFin");
        if(r.isEmpty())throw new NotFoundException("Reserva no encontrada.");
        var s=solicitud(user,(String)r.get("solicitud"));
        r.put("clienteId",s.get("clienteId"));
        r.put("cliente",s.get("nombres")+" "+s.get("apellidos"));
        r.put("pagos",db.lista("SELECT cod_pago,fecha,monto,metodo,referencia,estado FROM pago WHERE cod_reserva=:codigo ORDER BY fecha,cod_pago",Map.of("codigo",codigo),"codigo","fecha","monto","metodo","referencia","estado"));
        var pagado=db.primero("SELECT COALESCE(SUM(monto),0) FROM pago WHERE cod_reserva=:codigo AND estado='CONFIRMADO'",Map.of("codigo",codigo),"total");
        r.put("pagado",pagado.get("total"));
        r.put("cancelacionSolicitada",!db.lista("SELECT m.cod_mensaje FROM mensaje m JOIN conversacion c ON c.cod_conversacion=m.cod_conversacion WHERE c.id_usuario_cliente=:cliente AND m.id_remitente=:cliente AND m.contenido LIKE :prefijo LIMIT 1",Map.of("cliente",s.get("clienteId"),"prefijo","[SOLICITUD DE CANCELACIÓN] Reserva "+codigo+". Motivo:%"),"id").isEmpty());
        return r;
    }

    @Transactional
    public Map<String,String> registrarPago(AccesoService.Identidad user,String codigo,Pago datos) {
        db.lista("SELECT cod_reserva FROM reserva WHERE cod_reserva=:codigo FOR UPDATE",Map.of("codigo",codigo),"codigo");
        var r=reserva(user,codigo);
        if(!Set.of("PENDIENTE_ANTICIPO","CONFIRMADA").contains((String)r.get("estado")))throw new ClientErrorException("La reserva no admite nuevos pagos.",409);
        if(datos==null || !Boolean.TRUE.equals(datos.verificado()))throw new BadRequestException("Confirma la verificación del pago externo.");
        if(datos.fecha()==null || datos.fecha().isAfter(java.time.LocalDate.now()))throw new BadRequestException("Indica una fecha de pago válida.");
        BigDecimal total=new BigDecimal(r.get("total").toString());
        BigDecimal pagado=new BigDecimal(r.get("pagado").toString());
        if(datos.monto()==null || datos.monto().signum()<=0 || datos.monto().scale()>2 || datos.monto().compareTo(total.subtract(pagado))>0)throw new BadRequestException("Revisa el monto; debe ser positivo y no superar el saldo.");
        BigDecimal anticipo=total.multiply(new BigDecimal("0.5")).setScale(2,java.math.RoundingMode.CEILING);
        if(pagado.add(datos.monto()).compareTo(anticipo)<0)throw new BadRequestException("El anticipo obligatorio debe cubrir al menos el 50 % del total.");
        String metodo=AccesoService.texto(datos.metodo(),"el método de pago",50);
        String referencia=AccesoService.texto(datos.referencia(),"la referencia del pago",100);
        if(!db.lista("SELECT cod_pago FROM pago WHERE cod_reserva=:codigo AND referencia=:referencia AND estado='CONFIRMADO'",Map.of("codigo",codigo,"referencia",referencia),"codigo").isEmpty())throw new ClientErrorException("Esta referencia ya está registrada en la reserva.",409);
        String pago=GestionValidationService.codigo("PAG");
        db.ejecutar("INSERT INTO pago (cod_pago,cod_reserva,fecha,monto,metodo,referencia,estado) VALUES (:pago,:reserva,:fecha,:monto,:metodo,:referencia,'CONFIRMADO')",Map.of("pago",pago,"reserva",codigo,"fecha",datos.fecha(),"monto",datos.monto(),"metodo",metodo,"referencia",referencia));
        if("PENDIENTE_ANTICIPO".equals(r.get("estado"))) {
            db.ejecutar("UPDATE reserva SET estado='CONFIRMADA' WHERE cod_reserva=:codigo",Map.of("codigo",codigo));
            db.ejecutar("UPDATE viaje_confirmado SET estado='CONFIRMADO' WHERE cod_reserva=:codigo",Map.of("codigo",codigo));
            portal.notificar(((Number)r.get("clienteId")).longValue(),"Tu viaje está confirmado","El personal verificó el anticipo del 50 % para la reserva "+codigo+". Consulta el saldo en Mi viaje.");
        } else portal.notificar(((Number)r.get("clienteId")).longValue(),"Pago registrado","El personal verificó un nuevo pago para la reserva "+codigo+".");
        return Map.of("codigo",pago);
    }

    @Transactional
    public void registrarCancelacion(AccesoService.Identidad user,String codigo) {
        db.lista("SELECT cod_reserva FROM reserva WHERE cod_reserva=:codigo FOR UPDATE",Map.of("codigo",codigo),"codigo");
        var r=reserva(user,codigo);
        if(!Boolean.TRUE.equals(r.get("cancelacionSolicitada")))throw new ClientErrorException("Primero debe existir una solicitud de cancelación del cliente en la conversación.",409);
        if(!Set.of("PENDIENTE_ANTICIPO","CONFIRMADA").contains((String)r.get("estado")))throw new ClientErrorException("Esta reserva ya no admite cancelación.",409);
        db.ejecutar("UPDATE reserva SET estado='CANCELADA',observaciones=CONCAT(COALESCE(observaciones,''),:nota) WHERE cod_reserva=:codigo",Map.of("codigo",codigo,"nota","\nCancelación solicitada por el cliente y registrada por el usuario "+user.id()+". Sin devolución."));
        db.ejecutar("UPDATE viaje_confirmado SET estado='CANCELADO' WHERE cod_reserva=:codigo",Map.of("codigo",codigo));
        portal.notificar(((Number)r.get("clienteId")).longValue(),"Cancelación registrada","El personal registró tu solicitud de cancelación de la reserva "+codigo+". No se realizan devoluciones.");
    }
}
