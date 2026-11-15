package com.thakivoyage.resource;

import java.util.*;
import com.thakivoyage.service.*;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;

@Path("/api/inventario/operaciones") @Produces(MediaType.APPLICATION_JSON)
public class InventarioConsultaResource {
    @Inject InventarioConsultaService service;
    @Inject AccesoService acceso;
    private void personal(String h) {
        if(!Set.of("ADMIN","ADMINISTRATIVO").contains(acceso.autenticar(h).rol()))throw new ForbiddenException();
    }
    @GET public Map<String,Object> resumen(@HeaderParam("Authorization") String h){personal(h);return service.resumen();}
    @GET @Path("/documentos/{codigo}") public Map<String,Object> documento(@HeaderParam("Authorization") String h,@PathParam("codigo") String c){personal(h);return service.documento(c);}
}
