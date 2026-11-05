package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;

import java.time.LocalDate;

import org.junit.jupiter.api.Test;

import jakarta.ws.rs.BadRequestException;

class UnidadInventarioServiceTest {
    private UnidadInventarioService.UnidadRequest datos(String codigo, Integer calificacion, LocalDate fecha) {
        return new UnidadInventarioService.UnidadRequest(codigo, " ALM-001 ", " BUENO ",
            calificacion, fecha, null, true, false, "  Costura revisada  ");
    }

    @Test
    void normalizaCamposYConservaCalificacionOpcional() {
        var resultado = UnidadInventarioService.validar(datos(" CD001 ", null, LocalDate.now()));
        assertEquals("CD001", resultado.codigoUnico());
        assertEquals("ALM-001", resultado.codAlmacen());
        assertEquals("BUENO", resultado.estadoFisico());
        assertEquals("Costura revisada", resultado.observaciones());
        assertNull(resultado.calificacion());
        assertFalse(resultado.requiereArreglo());
        assertTrue(resultado.requiereCambio());
        assertFalse(resultado.requiereBaja());
    }

    @Test
    void rechazaCodigoVacioOLargo() {
        assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(datos("  ", null, LocalDate.now())));
        assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(datos("X".repeat(51), null, LocalDate.now())));
    }

    @Test
    void rechazaFechaAusenteOFutura() {
        assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(datos("CD001", null, null)));
        assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(datos("CD001", null, LocalDate.now().plusDays(1))));
    }

    @Test
    void rechazaCalificacionNegativaYSolicitudAusente() {
        assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(datos("CD001", -1, LocalDate.now())));
        assertThrows(BadRequestException.class, () -> UnidadInventarioService.validar(null));
    }
}
