package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.util.*;
import jakarta.ws.rs.NotFoundException;
import org.junit.jupiter.api.Test;

class PortalPrivacidadTest {
    private final AccesoService.Identidad cliente=new AccesoService.Identidad(7,"ana@example.test","Ana","CLIENTE");

    @Test void propuestaAjenaDevuelve404SinConsultarSuItinerario() {
        var service=new PortalService();
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){
                if(sql.contains("FROM persona")){assertEquals(cliente.email(),params.get("email"));return List.of(Map.of("codigo","PER-7"));}
                if(sql.contains("FROM cliente"))return List.of(Map.of("codigo","CLI-7","estado","ACTIVO"));
                fail("No debe consultar el itinerario de la propuesta ajena");return List.of();
            }
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){
                assertTrue(sql.contains("s.cod_cliente=:cliente"));
                assertEquals("CLI-7",params.get("cliente"));
                return Map.of();
            }
        };
        assertThrows(NotFoundException.class,()->service.propuesta(cliente,"PRP-AJENA"));
    }

    @Test void notificacionAjenaNoPuedeMarcarseLeida() {
        var service=new PortalService();
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){assertEquals(7L,params.get("usuario"));assertTrue(sql.contains("id_usuario=:usuario"));return List.of();}
            @Override public int ejecutar(String sql,Map<String,?> params){fail("No debe modificar una notificación ajena");return 0;}
        };
        assertThrows(NotFoundException.class,()->service.leer(cliente,99));
    }

    @Test void mensajesSiempreSeLimitanAlClienteAutenticado() {
        var service=new PortalService();
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){assertEquals(7L,params.get("usuario"));assertTrue(sql.contains("c.id_usuario_cliente=:usuario"));return List.of();}
        };
        assertTrue(service.mensajes(cliente).isEmpty());
    }
}
