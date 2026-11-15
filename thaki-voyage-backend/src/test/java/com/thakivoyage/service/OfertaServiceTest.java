package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.math.BigDecimal;
import java.util.*;
import jakarta.ws.rs.BadRequestException;
import org.junit.jupiter.api.Test;

class OfertaServiceTest {
    @Test void hotelSinCodigosNoReemplazaLaPreferenciaLibre() {
        var service = new OfertaService();
        service.db = new PortalQuery() {
            @Override public Map<String, Object> primero(String sql, Map<String, ?> params, String... cols) {
                fail("No debe consultar el catálogo si el cliente no eligió un código");
                return Map.of();
            }
        };
        assertNull(service.etiquetaHotel(null, null));
        assertNull(service.etiquetaHotel("  ", "HAB-1"));
        assertNull(service.etiquetaTransporte(""));
    }

    @Test void hotelVigenteArmaUnaEtiquetaClara() {
        var service = new OfertaService();
        service.db = new PortalQuery() {
            @Override public Map<String, Object> primero(String sql, Map<String, ?> params, String... cols) {
                assertEquals("HOT-UYU", params.get("hotel"));
                assertEquals("HAB-UYU-DBL", params.get("habitacion"));
                assertTrue(sql.contains("h.estado = 'ACTIVO'"));
                return Map.of("hotel", "Hotel Luna Salada", "ciudad", "Uyuni", "habitacion", "Doble",
                    "precio", new BigDecimal("350.00"), "moneda", "BOB", "desayuno", 1);
            }
        };
        assertEquals("Hotel Luna Salada, Uyuni · Doble · 350 BOB/noche · desayuno", service.etiquetaHotel("HOT-UYU", "HAB-UYU-DBL"));
    }

    @Test void opcionInexistenteNoSeGuarda() {
        var service = new OfertaService();
        service.db = new PortalQuery() {
            @Override public Map<String, Object> primero(String sql, Map<String, ?> params, String... cols) {
                return Map.of();
            }
        };
        assertThrows(BadRequestException.class, () -> service.etiquetaTransporte("TRS-NO"));
    }
}
