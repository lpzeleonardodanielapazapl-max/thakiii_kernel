package com.thakivoyage.resource;

import com.thakivoyage.service.CatalogoViajeService;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import java.util.*;

@Path("/api/v1/{modulo:hoteles|habitaciones|tarifas-hotel|vehiculos|conductores|servicios-transporte}")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class CatalogoViajeResource {
    @Inject CatalogoViajeService service;
    @GET public List<Map<String,Object>> listar(@PathParam("modulo") String modulo) { return service.listar(modulo); }
    @GET @Path("/{codigo}") public Map<String,Object> obtener(@PathParam("modulo") String modulo,@PathParam("codigo") String codigo) { return service.obtener(modulo,codigo); }
    @POST public Response crear(@PathParam("modulo") String modulo,Map<String,Object> datos) { return Response.status(201).entity(service.guardar(modulo,null,datos)).build(); }
    @PUT @Path("/{codigo}") public Map<String,Object> actualizar(@PathParam("modulo") String modulo,@PathParam("codigo") String codigo,Map<String,Object> datos) { return service.guardar(modulo,codigo,datos); }
}
