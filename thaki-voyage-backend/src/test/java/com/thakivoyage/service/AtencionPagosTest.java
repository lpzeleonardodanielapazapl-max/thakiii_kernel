package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.*;
import jakarta.ws.rs.*;
import org.junit.jupiter.api.Test;

class AtencionPagosTest {
    private final AccesoService.Identidad staff=new AccesoService.Identidad(1,"staff@example.test","Asesor","ADMINISTRATIVO");
    private final List<String> escrituras=new ArrayList<>();
    private AtencionService service(boolean cancelacion,String estado) {
        var service=new AtencionService(){
            @Override public Map<String,Object> reserva(AccesoService.Identidad u,String codigo){return PortalQuery.valores("codigo",codigo,"estado",estado,"total",new BigDecimal("1000.00"),"pagado",BigDecimal.ZERO,"clienteId",7,"cancelacionSolicitada",cancelacion);}
        };
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){return List.of();}
            @Override public int ejecutar(String sql,Map<String,?> params){escrituras.add(sql);return 1;}
        };
        service.portal=new PortalService(){@Override public void notificar(long id,String titulo,String contenido){assertEquals(7,id);}};
        return service;
    }

    @Test void anticipoInsuficienteONoVerificadoNoConfirmaElViaje() {
        var service=service(false,"PENDIENTE_ANTICIPO");
        assertThrows(BadRequestException.class,()->service.registrarPago(staff,"RES-1",new AtencionService.Pago(LocalDate.now(),new BigDecimal("499.99"),"Transferencia","REF1",true)));
        assertThrows(BadRequestException.class,()->service.registrarPago(staff,"RES-1",new AtencionService.Pago(LocalDate.now(),new BigDecimal("500.00"),"Transferencia","REF1",false)));
        assertTrue(escrituras.isEmpty());
    }

    @Test void anticipoVerificadoConfirmaReservaYViaje() {
        var result=service(false,"PENDIENTE_ANTICIPO").registrarPago(staff,"RES-1",new AtencionService.Pago(LocalDate.now(),new BigDecimal("500.00"),"Transferencia","REF1",true));
        assertTrue(result.containsKey("codigo"));
        assertEquals(3,escrituras.size());
        assertTrue(escrituras.get(1).contains("estado='CONFIRMADA'"));
        assertTrue(escrituras.get(2).contains("estado='CONFIRMADO'"));
    }

    @Test void pagosSuperioresAlSaldoYFechasFuturasSonRechazados() {
        var service=service(false,"PENDIENTE_ANTICIPO");
        assertThrows(BadRequestException.class,()->service.registrarPago(staff,"RES-1",new AtencionService.Pago(LocalDate.now(),new BigDecimal("1000.01"),"Transferencia","REF1",true)));
        assertThrows(BadRequestException.class,()->service.registrarPago(staff,"RES-1",new AtencionService.Pago(LocalDate.now().plusDays(1),new BigDecimal("500.00"),"Transferencia","REF1",true)));
        assertTrue(escrituras.isEmpty());
    }

    @Test void empresaNoPuedeCancelarSinSolicitudDelCliente() {
        assertEquals(409,assertThrows(ClientErrorException.class,()->service(false,"CONFIRMADA").registrarCancelacion(staff,"RES-1")).getResponse().getStatus());
        assertTrue(escrituras.isEmpty());
    }

    @Test void cancelacionSolicitadaConservaLosPagos() {
        service(true,"CONFIRMADA").registrarCancelacion(staff,"RES-1");
        assertEquals(2,escrituras.size());
        assertTrue(escrituras.getFirst().contains("CANCELADA"));
        assertTrue(escrituras.stream().noneMatch(sql->sql.contains("DELETE")||sql.contains("UPDATE pago")));
    }
}
