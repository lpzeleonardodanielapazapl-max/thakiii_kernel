package com.thakivoyage.resource;

import static org.junit.jupiter.api.Assertions.*;
import java.sql.SQLException;
import org.junit.jupiter.api.Test;
import jakarta.ws.rs.BadRequestException;

class GestionExceptionMapperTest {
    @Test void conservaCabecerasDeAutenticacionYMetodosPermitidos() {
        var mapper = new GestionExceptionMapper();
        try (var unauthorized = mapper.toResponse(new jakarta.ws.rs.NotAuthorizedException("Bearer"));
             var notAllowed = mapper.toResponse(new jakarta.ws.rs.NotAllowedException("GET", new String[]{"HEAD"}))) {
            assertEquals(401, unauthorized.getStatus());
            assertEquals("Bearer", unauthorized.getHeaderString("WWW-Authenticate"));
            assertTrue(notAllowed.getAllowedMethods().containsAll(java.util.Set.of("GET", "HEAD")));
        }
    }

    @Test void lasReferenciasYDuplicadosTienenRespuestaClara() {
        var mapper = new GestionExceptionMapper();
        try (var duplicate = mapper.toResponse(new RuntimeException(new SQLException("private SQL", "23000", 1062)));
             var linked = mapper.toResponse(new RuntimeException(new SQLException("private SQL", "23000", 1451)));
             var missing = mapper.toResponse(new RuntimeException(new SQLException("private SQL", "23000", 1452)))) {
            assertEquals(409, duplicate.getStatus()); assertEquals(409, linked.getStatus()); assertEquals(422, missing.getStatus());
            assertFalse(linked.getEntity().toString().contains("private SQL"));
        }
    }

    @Test void validacionYRolesRespetanElContrato() {
        try (var invalid = new GestionExceptionMapper().toResponse(new BadRequestException("Revisa la fecha."));
             var createRole = new RolResource().crear(null);
             var deleteRole = new RolResource().eliminar("ROL-1")) {
            assertEquals(422, invalid.getStatus()); assertEquals(405, createRole.getStatus()); assertEquals(405, deleteRole.getStatus());
        }
    }
}
