package com.thakivoyage.resource;

import java.util.List;
import java.util.Map;

import com.thakivoyage.service.RegistroProductoService;

import jakarta.inject.Inject;
import jakarta.ws.rs.BadRequestException;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("/api/registro-productos")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class RegistroProductoResource {

    @Inject
    RegistroProductoService servicio;

    @GET
    @Path("/catalogos")
    public Map<String, List<RegistroProductoService.Opcion>> catalogos() {
        return servicio.catalogos();
    }

    @POST
    public Response registrar(
        RegistroProductoService.ProductoRequest datos
    ) {
        try {
            var producto = servicio.registrar(datos);

            return Response.status(Response.Status.CREATED)
                .entity(producto)
                .build();

        } catch (BadRequestException e) {
            return Response.status(422)
                .entity(Map.of("mensaje", e.getMessage()))
                .build();
        }
    }
}