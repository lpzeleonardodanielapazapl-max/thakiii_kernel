package com.thakivoyage.resource;

import java.util.HashMap;
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
public class HealtResource {

    @Inject
    EntityManager em;

    @GET
    @Path("/ping")
    public Response ping() {
        return Response.ok(Map.of(
            "status", "ok",
            "service", "thaki-api",
            "version", "1.0.0",
            "database", "mysql"
        )).build();
    }

    @GET
    @Path("/db")
    public Response checkDatabase() {
        Map<String, Object> result = new HashMap<>();
        try {
            Object tablas = em.createNativeQuery(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'thaki_voyage' AND table_type = 'BASE TABLE'"
            ).getSingleResult();

            Object vistas = em.createNativeQuery(
                "SELECT COUNT(*) FROM information_schema.views WHERE table_schema = 'thaki_voyage'"
            ).getSingleResult();

            Object triggers = em.createNativeQuery(
                "SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema = 'thaki_voyage'"
            ).getSingleResult();

            Object roles = em.createNativeQuery("SELECT COUNT(*) FROM rol").getSingleResult();
            Object marcas = em.createNativeQuery("SELECT COUNT(*) FROM marca").getSingleResult();
            Object clases = em.createNativeQuery("SELECT COUNT(*) FROM clase_producto").getSingleResult();
            Object version = em.createNativeQuery("SELECT VERSION()").getSingleResult();

            result.put("status", "ok");
            result.put("database", "thaki_voyage");
            result.put("motor", "MySQL " + version);
            result.put("tablas", tablas);
            result.put("vistas", vistas);
            result.put("triggers", triggers);
            result.put("roles_registrados", roles);
            result.put("marcas_registradas", marcas);
            result.put("clases_producto", clases);

            return Response.ok(result).build();
        } catch (Exception e) {
            result.put("status", "error");
            result.put("message", e.getMessage());
            return Response.status(500).entity(result).build();
        }
    }
}