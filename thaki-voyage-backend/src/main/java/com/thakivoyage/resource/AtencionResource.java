package com.thakivoyage.resource;

import java.util.Map;
import com.thakivoyage.service.*;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;

@Path("/api/atencion") @Produces(MediaType.APPLICATION_JSON) @Consumes(MediaType.APPLICATION_JSON)
public class AtencionResource {
    @Inject AtencionService service;
    @Inject AccesoService acceso;
    private AccesoService.Identidad user(String header) {
        var user=acceso.autenticar(header);
        if(!java.util.Set.of("ADMIN","ADMINISTRATIVO").contains(user.rol()))throw new ForbiddenException();
        return user;
    }
    @GET @Path("/panel") public Map<String,Object> panel(@HeaderParam("Authorization") String h){return service.panel(user(h));}
    @GET @Path("/conversaciones/{id}") public Map<String,Object> conversacion(@HeaderParam("Authorization") String h,@PathParam("id")long id){return service.conversacion(user(h),id);}
    @POST @Path("/conversaciones/{id}/mensajes") public void responder(@HeaderParam("Authorization")String h,@PathParam("id")long id,PortalService.Texto datos){service.responder(user(h),id,datos);}
    @GET @Path("/solicitudes/{codigo}") public Map<String,Object> solicitud(@HeaderParam("Authorization")String h,@PathParam("codigo")String codigo){return service.solicitud(user(h),codigo);}
    @POST @Path("/propuestas") public Map<String,String> proponer(@HeaderParam("Authorization")String h,AtencionService.Propuesta datos){return service.proponer(user(h),datos);}
    @POST @Path("/propuestas/{codigo}/aceptacion") public Map<String,String> aceptar(@HeaderParam("Authorization")String h,@PathParam("codigo")String codigo){return service.aceptar(user(h),codigo);}
    @GET @Path("/reservas/{codigo}") public Map<String,Object> reserva(@HeaderParam("Authorization")String h,@PathParam("codigo")String codigo){return service.reserva(user(h),codigo);}
    @POST @Path("/reservas/{codigo}/pagos") public Map<String,String> pago(@HeaderParam("Authorization")String h,@PathParam("codigo")String codigo,AtencionService.Pago datos){return service.registrarPago(user(h),codigo,datos);}
    @POST @Path("/reservas/{codigo}/cancelacion") public void cancelar(@HeaderParam("Authorization")String h,@PathParam("codigo")String codigo){service.registrarCancelacion(user(h),codigo);}
}
