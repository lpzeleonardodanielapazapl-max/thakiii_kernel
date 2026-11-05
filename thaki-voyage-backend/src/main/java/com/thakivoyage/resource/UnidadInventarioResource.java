package com.thakivoyage.resource;

import java.sql.SQLException;
import java.util.Map;

import com.thakivoyage.service.UnidadInventarioService;

import jakarta.inject.Inject;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.PUT;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.PathParam;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.WebApplicationException;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("/api/inventario/productos/{codigo}/unidades")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class UnidadInventarioResource {
    @Inject
    UnidadInventarioService servicio;

    @GET
    public Response consultar(@PathParam("codigo") String codigo) {
        try {
            return Response.ok(servicio.consultar(codigo)).build();
        } catch (WebApplicationException e) {
            return error(e);
        }
    }

    @POST
    public Response registrar(@PathParam("codigo") String codigo,
        UnidadInventarioService.UnidadRequest datos) {
        try {
            return Response.status(201)
                .entity(Map.of("codigoUnico", servicio.registrar(codigo, datos))).build();
        } catch (WebApplicationException e) {
            return error(e);
        } catch (RuntimeException e) {
            // La restricción UNIQUE existente también protege solicitudes simultáneas.
            for (Throwable causa = e; causa != null; causa = causa.getCause()) {
                if (causa instanceof SQLException sql && sql.getErrorCode() == 1062) {
                    return Response.status(409)
                        .entity(Map.of("mensaje", "Ya existe una unidad con ese código único.")).build();
                }
            }
            throw e;
        }
    }

    @GET
    @Path("/{id}")
    public UnidadInventarioService.Unidad consultarUnidad(@PathParam("codigo") String codigo, @PathParam("id") long id) {
        return servicio.consultarUnidad(codigo, id);
    }

    @PUT
    @Path("/{id}")
    public UnidadInventarioService.Unidad actualizar(@PathParam("codigo") String codigo, @PathParam("id") long id,
        UnidadInventarioService.UnidadRequest datos) {
        return servicio.actualizar(codigo, id, datos);
    }

    private Response error(WebApplicationException e) {
        int estado = e.getResponse().getStatus();
        return Response.status(estado == 400 ? 422 : estado)
            .entity(Map.of("mensaje", e.getMessage())).build();
    }
}
