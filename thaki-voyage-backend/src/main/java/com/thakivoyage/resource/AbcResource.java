package com.thakivoyage.resource;

import com.thakivoyage.service.AbcService;

import jakarta.inject.Inject;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;

@Path("/api/abc")
@Produces(MediaType.APPLICATION_JSON)
public class AbcResource {

    @Inject
    AbcService abcService;

    @GET
    public AbcService.ResultadoAbc consultar() {
        return abcService.calcular();
    }
}