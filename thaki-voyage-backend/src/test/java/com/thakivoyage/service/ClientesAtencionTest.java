package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.util.*;
import org.junit.jupiter.api.Test;
import jakarta.ws.rs.NotFoundException;

class ClientesAtencionTest {
    private final AccesoService.Identidad asesor = new AccesoService.Identidad(2,"asesor@example.test","Asesor","ADMINISTRATIVO");

    @Test
    void listaRestringeAsignacionYAvisosAlUsuarioActual() {
        var service = new AtencionService();
        service.db = new PortalQuery() {
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols) {
                assertEquals(2L,params.get("usuario"));
                assertEquals(0,params.get("admin"));
                assertTrue(sql.contains("a.id_usuario_ase=:usuario"));
                assertTrue(sql.contains("ml.id_usuario=:usuario"));
                return List.of();
            }
        };
        assertTrue(service.clientes(asesor).isEmpty());
    }

    @Test
    void fichaAjenaNoConsultaSolicitudesNiReservas() {
        var service = new AtencionService() {
            @Override public List<Map<String,Object>> clientes(AccesoService.Identidad user) { return List.of(); }
        };
        assertThrows(NotFoundException.class, () -> service.cliente(asesor,"AJENO"));
        assertThrows(NotFoundException.class, () -> service.revisarCliente(asesor,"AJENO"));
    }

    @Test
    void marcarLeidosCompruebaAccesoAntesDeEscribir() {
        var service = new AtencionService() {
            @Override public Map<String,Object> conversacion(AccesoService.Identidad user,long id) { throw new NotFoundException(); }
        };
        assertThrows(NotFoundException.class, () -> service.leerConversacion(asesor,9));
    }
}
