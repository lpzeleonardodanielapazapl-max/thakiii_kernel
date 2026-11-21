package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;

import java.time.LocalDate;

import org.junit.jupiter.api.Test;

import jakarta.ws.rs.BadRequestException;

class UnidadInventarioServiceTest {
    @Test
    void edicionLeeLaBajaDespuesDeBloquearLaUnidadYNoEscribeSobreSuHistorial() {
        var bloqueada = new java.util.concurrent.atomic.AtomicBoolean();
        var service = new UnidadInventarioService() {
            @Override public Unidad consultarUnidad(String codigo, long id) {
                assertTrue(bloqueada.get(), "Debe bloquear antes de consultar el estado de la unidad.");
                return new Unidad(id, "CD001", "Principal", "BUENO", 8, false, false, false,
                    "2026-01-01", "2026-02-01", null, "ALM-001");
            }
        };
        var query = (jakarta.persistence.Query) java.lang.reflect.Proxy.newProxyInstance(getClass().getClassLoader(),
            new Class<?>[]{jakarta.persistence.Query.class}, (p,m,a) -> switch (m.getName()) {
                case "setParameter" -> p;
                case "getResultList" -> { bloqueada.set(true); yield java.util.List.of(1); }
                default -> throw new AssertionError("No debe modificar una unidad dada de baja: " + m.getName());
            });
        service.entityManager = (jakarta.persistence.EntityManager) java.lang.reflect.Proxy.newProxyInstance(
            getClass().getClassLoader(), new Class<?>[]{jakarta.persistence.EntityManager.class}, (p,m,a) -> {
                assertEquals("createNativeQuery", m.getName());
                assertTrue(a[0].toString().contains("FOR UPDATE"));
                return query;
            });
        assertThrows(BadRequestException.class,
            () -> service.actualizar("PRO-1", 1, datos("CD001", 8, LocalDate.now())));
    }

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
    void aceptaLongitudesMaximasYLimitesDeCalificacion() {
        var solicitud = new UnidadInventarioService.UnidadRequest("C".repeat(50), "A".repeat(20),
            "E".repeat(20), 10, LocalDate.now(), false, false, false, "O".repeat(2000));

        var resultado = UnidadInventarioService.validar(solicitud);

        assertEquals(50, resultado.codigoUnico().length());
        assertEquals(20, resultado.codAlmacen().length());
        assertEquals(20, resultado.estadoFisico().length());
        assertEquals(2000, resultado.observaciones().length());
        assertEquals(10, resultado.calificacion());
        assertEquals(1, UnidadInventarioService.validar(datos("CD002", 1, LocalDate.now())).calificacion());
    }

    @Test
    void rechazaCamposQueSuperanSuLongitudMaximaConUnErrorDescriptivo() {
        var base = datos("CD001", null, LocalDate.now());

        var errorCodigo = assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(new UnidadInventarioService.UnidadRequest(
                "C".repeat(51), base.codAlmacen(), base.estadoFisico(), base.calificacion(), base.fechaAlta(),
                base.requiereArreglo(), base.requiereCambio(), base.requiereBaja(), base.observaciones())));
        assertEquals("El campo código único admite hasta 50 caracteres.", errorCodigo.getMessage());

        var errorAlmacen = assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(new UnidadInventarioService.UnidadRequest(
                base.codigoUnico(), "A".repeat(21), base.estadoFisico(), base.calificacion(), base.fechaAlta(),
                base.requiereArreglo(), base.requiereCambio(), base.requiereBaja(), base.observaciones())));
        assertEquals("El campo almacén admite hasta 20 caracteres.", errorAlmacen.getMessage());

        var errorEstado = assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(new UnidadInventarioService.UnidadRequest(
                base.codigoUnico(), base.codAlmacen(), "E".repeat(21), base.calificacion(), base.fechaAlta(),
                base.requiereArreglo(), base.requiereCambio(), base.requiereBaja(), base.observaciones())));
        assertEquals("El campo estado físico admite hasta 20 caracteres.", errorEstado.getMessage());

        var errorObservaciones = assertThrows(BadRequestException.class,
            () -> UnidadInventarioService.validar(new UnidadInventarioService.UnidadRequest(
                base.codigoUnico(), base.codAlmacen(), base.estadoFisico(), base.calificacion(), base.fechaAlta(),
                base.requiereArreglo(), base.requiereCambio(), base.requiereBaja(), "O".repeat(2001))));
        assertEquals("El campo observaciones admite hasta 2000 caracteres.", errorObservaciones.getMessage());
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
        assertThrows(BadRequestException.class, () -> UnidadInventarioService.validar(datos("CD001", 0, LocalDate.now())));
        assertThrows(BadRequestException.class, () -> UnidadInventarioService.validar(datos("CD001", 11, LocalDate.now())));
    }
}
