package com.thakivoyage.resource;
import java.util.*;
import com.thakivoyage.service.*;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
@Path("/api/inventario/catalogos") @Produces(MediaType.APPLICATION_JSON) @Consumes(MediaType.APPLICATION_JSON)
public class CatalogoInventarioResource {
    @Inject CatalogoInventarioService service;
    @Inject AccesoService acceso;
    private AccesoService.Identidad user(String h){var u=acceso.autenticar(h);if(!Set.of("ADMIN","ADMINISTRATIVO").contains(u.rol()))throw new ForbiddenException();return u;}
    @GET public Map<String,Object> listar(@HeaderParam("Authorization") String h){user(h);return service.listar();}
    @POST @Path("/{tipo}") public Map<String,String> crear(@HeaderParam("Authorization") String h,@PathParam("tipo") String t,Map<String,Object> d){return service.guardar(user(h),t,null,d);}
    @PUT @Path("/{tipo}/{codigo}") public Map<String,String> editar(@HeaderParam("Authorization") String h,@PathParam("tipo") String t,@PathParam("codigo") String c,Map<String,Object> d){return service.guardar(user(h),t,c,d);}
}
