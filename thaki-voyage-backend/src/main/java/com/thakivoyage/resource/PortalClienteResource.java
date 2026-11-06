package com.thakivoyage.resource;

import com.thakivoyage.service.*;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.*;
import java.util.*;

@Path("/api/cliente")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class PortalClienteResource {
    @Inject PortalService portal;
    @Inject AccesoService acceso;
    private AccesoService.Identidad user(String header) {
        var user=acceso.autenticar(header);
        if (!user.rol().equals("CLIENTE")) throw new ForbiddenException();
        return user;
    }
    @GET @Path("/resumen") public Map<String,Object> resumen(@HeaderParam("Authorization") String h) { return portal.resumen(user(h)); }
    @GET @Path("/perfil") public Map<String,Object> perfil(@HeaderParam("Authorization") String h) { return portal.perfil(user(h)); }
    @PUT @Path("/perfil") public Map<String,Object> perfil(@HeaderParam("Authorization") String h,com.thakivoyage.dto.request.PersonaRequest datos) { if(datos==null)throw new BadRequestException("Completa tus datos."); return portal.guardarPerfil(user(h),datos); }
    @POST @Path("/asignacion") public Map<String,Object> asignar(@HeaderParam("Authorization") String h) { return portal.asignar(user(h)); }
    @POST @Path("/solicitudes") public Map<String,Object> solicitar(@HeaderParam("Authorization") String h,PortalService.Viaje datos) { return portal.solicitar(user(h),datos); }
    @GET @Path("/mensajes") public List<Map<String,Object>> mensajes(@HeaderParam("Authorization") String h) { return portal.mensajes(user(h)); }
    @POST @Path("/mensajes") public Map<String,String> enviar(@HeaderParam("Authorization") String h,PortalService.Texto datos) { portal.enviar(user(h),datos); return Map.of("mensaje","Mensaje enviado."); }
    @GET @Path("/propuestas/{codigo}") public Map<String,Object> propuesta(@HeaderParam("Authorization") String h,@PathParam("codigo") String codigo) { return portal.propuesta(user(h),codigo); }
    @POST @Path("/propuestas/{codigo}/aceptar") public Map<String,String> aceptar(@HeaderParam("Authorization") String h,@PathParam("codigo") String codigo) { portal.aceptar(user(h),codigo); return Map.of("mensaje","Tu aceptación fue comunicada al asesor. El administrativo debe registrarla."); }
    @POST @Path("/reservas/{codigo}/cancelacion") public Map<String,String> cancelar(@HeaderParam("Authorization") String h,@PathParam("codigo") String codigo,PortalService.Texto datos) { portal.cancelar(user(h),codigo,datos); return Map.of("mensaje","Solicitud enviada. El personal registrará la cancelación."); }
    @GET @Path("/notificaciones") public List<Map<String,Object>> notificaciones(@HeaderParam("Authorization") String h) { return portal.notificaciones(user(h)); }
    @POST @Path("/notificaciones/{id}/leer") public void leer(@HeaderParam("Authorization") String h,@PathParam("id") long id) { portal.leer(user(h),id); }
}
