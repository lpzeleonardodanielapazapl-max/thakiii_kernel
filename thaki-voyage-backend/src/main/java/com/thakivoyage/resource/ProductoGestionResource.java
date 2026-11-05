package com.thakivoyage.resource;

import com.thakivoyage.service.ProductoGestionService;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;

@Path("/api/inventario/productos/{codigo}")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class ProductoGestionResource {
    @Inject ProductoGestionService servicio;

    @GET
    public ProductoGestionService.Producto consultar(@PathParam("codigo") String codigo) {
        return servicio.consultar(codigo);
    }

    @PUT
    public ProductoGestionService.Producto actualizar(@PathParam("codigo") String codigo, ProductoGestionService.Datos datos) {
        return servicio.actualizar(codigo, datos);
    }
}
