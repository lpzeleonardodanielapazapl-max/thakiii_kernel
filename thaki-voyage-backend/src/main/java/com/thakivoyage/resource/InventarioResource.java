package com.thakivoyage.resource;

import java.util.List;

import com.thakivoyage.service.InventarioService;
import com.thakivoyage.service.InventarioService.ProductoResumen;

import jakarta.inject.Inject;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.QueryParam;
import jakarta.ws.rs.DefaultValue;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;

@Path("/api/inventario")
@Produces(MediaType.APPLICATION_JSON)
public class InventarioResource {

    @Inject
    InventarioService inventarioService;

    @GET
    @Path("/productos")
    public List<ProductoResumen> listarProductos(@QueryParam("estado") @DefaultValue("ACTIVO") String estado) {
        return inventarioService.listarProductos(estado);
    }
}
