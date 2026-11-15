package com.thakivoyage.resource;

import java.util.LinkedHashMap;
import java.util.Map;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("/api/v1/health")
@Produces(MediaType.APPLICATION_JSON)
public class SaludResource {
    @Inject EntityManager em;

    @GET
    @Path("/ping")
    public Response ping() {
        return Response.ok(Map.of("status", "ok", "service", "thaki-api", "version", "1.0.0")).build();
    }

    @GET
    @Path("/db")
    public Response checkDatabase() {
        Map<String, Object> result = new LinkedHashMap<>();
        try {
            Object schema = em.createNativeQuery("SELECT DATABASE()").getSingleResult();
            Object tablas = em.createNativeQuery(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
            ).getSingleResult();
            Object hoteles = em.createNativeQuery("SELECT COUNT(*) FROM hotel WHERE estado = 'ACTIVO'").getSingleResult();
            Object traslados = em.createNativeQuery("SELECT COUNT(*) FROM servicio_transporte WHERE estado = 'ACTIVO'").getSingleResult();
            Object version = em.createNativeQuery("SELECT VERSION()").getSingleResult();
            result.put("status", "ok");
            result.put("database", schema);
            result.put("motor", "MySQL " + version);
            result.put("tablas", tablas);
            result.put("hoteles_activos", hoteles);
            result.put("traslados_activos", traslados);
            return Response.ok(result).build();
        } catch (Exception e) {
            result.put("status", "error");
            result.put("message", e.getMessage());
            return Response.status(500).entity(result).build();
        }
    }
}
