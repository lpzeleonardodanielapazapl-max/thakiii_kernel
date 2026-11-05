package com.thakivoyage.resource;

import static org.junit.jupiter.api.Assertions.*;

import java.sql.SQLException;
import java.util.Map;

import org.junit.jupiter.api.Test;

import com.thakivoyage.service.UnidadInventarioService;

import jakarta.ws.rs.BadRequestException;
import jakarta.ws.rs.ClientErrorException;
import jakarta.ws.rs.NotFoundException;

class UnidadInventarioResourceTest {
    private UnidadInventarioResource recurso(RuntimeException fallo) {
        var recurso = new UnidadInventarioResource();
        recurso.servicio = new UnidadInventarioService() {
            @Override
            public String registrar(String codigo, UnidadRequest datos) {
                if (fallo != null) {
                    throw fallo;
                }
                return "CD001";
            }
        };
        return recurso;
    }

    @Test
    void registroExitosoDevuelve201YCodigo() {
        try (var respuesta = recurso(null).registrar("PRO-1", null)) {
            assertEquals(201, respuesta.getStatus());
            assertEquals(Map.of("codigoUnico", "CD001"), respuesta.getEntity());
        }
    }

    @Test
    void validacionDevuelve422ConMensaje() {
        try (var respuesta = recurso(new BadRequestException("Revisa el almacén."))
            .registrar("PRO-1", null)) {
            assertEquals(422, respuesta.getStatus());
            assertEquals(Map.of("mensaje", "Revisa el almacén."), respuesta.getEntity());
        }
    }

    @Test
    void conserva404Y409() {
        try (var respuesta = recurso(new NotFoundException("Producto no encontrado."))
            .registrar("PRO-1", null)) {
            assertEquals(404, respuesta.getStatus());
        }
        try (var respuesta = recurso(new ClientErrorException("Código repetido.", 409))
            .registrar("PRO-1", null)) {
            assertEquals(409, respuesta.getStatus());
        }
    }

    @Test
    void duplicadoConcurrenteDeMysqlSeConvierteEn409() {
        var fallo = new RuntimeException(new SQLException("Duplicate entry", "23000", 1062));
        try (var respuesta = recurso(fallo).registrar("PRO-1", null)) {
            assertEquals(409, respuesta.getStatus());
        }
    }

    @Test
    void otrosFallosDePersistenciaNoSePresentanComoDuplicados() {
        var fallo = new RuntimeException(new SQLException("Connection lost", "08000", 0));
        assertSame(fallo, assertThrows(RuntimeException.class,
            () -> recurso(fallo).registrar("PRO-1", null)));
    }
}
