package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.util.*;
import jakarta.ws.rs.*;
import org.junit.jupiter.api.Test;

class AtencionConversacionTest {
    private final AccesoService.Identidad asesor=new AccesoService.Identidad(2,"asesor@example.test","Asesor","ADMINISTRATIVO");

    @Test
    void conversacionAjenaNoExponeNiNombreNiMensajes() {
        var service=new AtencionService();
        service.db=new PortalQuery(){
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){
                assertEquals(2L,params.get("usuario"));
                assertEquals(0,params.get("admin"));
                assertTrue(sql.contains("c.id_usuario_asesor=:usuario"));
                return Map.of();
            }
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){fail("No debe leer el historial ajeno");return List.of();}
        };
        assertThrows(NotFoundException.class,()->service.conversacion(asesor,99));
    }

    @Test
    void conversacionCerradaNoAdmiteRespuestasPorLaApi() {
        var service=new AtencionService(){@Override public Map<String,Object> conversacion(AccesoService.Identidad u,long id){return Map.of("estado","CERRADA","clienteId",7);}};
        assertEquals(409,assertThrows(ClientErrorException.class,()->service.responder(asesor,1,new PortalService.Texto("Hola"))).getResponse().getStatus());
    }

    @Test
    void respuestaSeGuardaConElUsuarioAutenticadoYNotificaAlCliente() {
        var service=new AtencionService(){@Override public Map<String,Object> conversacion(AccesoService.Identidad u,long id){return Map.of("estado","ABIERTA","clienteId",7);}};
        List<String> acciones=new ArrayList<>();
        service.portal=new PortalService(){
            @Override public void insertarMensaje(long conversacion,long remitente,String texto){assertEquals(1,conversacion);assertEquals(2,remitente);assertEquals("Hola",texto);acciones.add("mensaje");}
            @Override public void notificar(long usuario,String titulo,String texto){assertEquals(7,usuario);acciones.add("notificacion");}
        };
        service.responder(asesor,1,new PortalService.Texto(" Hola "));
        assertEquals(List.of("mensaje","notificacion"),acciones);
    }
}
