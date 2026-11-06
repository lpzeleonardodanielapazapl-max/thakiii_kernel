package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.math.BigDecimal;
import java.util.*;
import jakarta.ws.rs.*;
import org.junit.jupiter.api.Test;

class AtencionPropuestasTest {
    private final AccesoService.Identidad asesor=new AccesoService.Identidad(2,"asesor@example.test","Asesor","ADMINISTRATIVO");

    @Test
    void validaTodosLosDiasAntesDeGuardarYNormalizaServiciosOpcionales() {
        var dias=AtencionService.validarDias(List.of(new AtencionService.Dia(99," Llegada\nTraslado "," Hotel "),new AtencionService.Dia(99," Recorrido ","  ")));
        assertEquals(1,dias.getFirst().dia());
        assertEquals(2,dias.get(1).dia());
        assertEquals("Llegada\nTraslado",dias.getFirst().descripcion());
        assertEquals("Hotel",dias.getFirst().servicios());
        assertNull(dias.get(1).servicios());
        assertThrows(BadRequestException.class,()->AtencionService.validarDias(null));
        assertThrows(BadRequestException.class,()->AtencionService.validarDias(List.of()));
        assertThrows(BadRequestException.class,()->AtencionService.validarDias(Arrays.asList((AtencionService.Dia)null)));
        assertThrows(BadRequestException.class,()->AtencionService.validarDias(List.of(new AtencionService.Dia(1,"  ",null))));
        assertThrows(BadRequestException.class,()->AtencionService.validarDias(Collections.nCopies(121,new AtencionService.Dia(1,"Día",null))));
        assertThrows(BadRequestException.class,()->AtencionService.validarDias(List.of(new AtencionService.Dia(1,"Día","x".repeat(2001)))));
    }

    @Test
    void nuevaVersionGuardaDiasPropiosYConservaElContenidoDeLasAnteriores() {
        List<String> operaciones=new ArrayList<>();
        List<Map<String,?>> parametros=new ArrayList<>();
        var service=new AtencionService(){@Override public Map<String,Object> solicitud(AccesoService.Identidad user,String codigo){return Map.of("clienteId",7);}};
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){return List.of();}
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){return Map.of("numero",3);}
            @Override public int ejecutar(String sql,Map<String,?> params){operaciones.add(sql);parametros.add(params);return 1;}
        };
        service.portal=new PortalService(){@Override public void notificar(long id,String titulo,String contenido){assertEquals(7,id);}};
        var resultado=service.proponer(asesor,new AtencionService.Propuesta("SOL-1","Ruta","Descripción",new BigDecimal("1000.00"),"USD",List.of(new AtencionService.Dia(9,"Llegada","Traslado"),new AtencionService.Dia(3,"Caminata","Guía"))));
        assertEquals(3,resultado.get("version"));
        assertEquals(4,operaciones.size());
        assertTrue(operaciones.getFirst().contains("SET estado='REEMPLAZADA'"));
        assertTrue(operaciones.getFirst().contains("estado='ENVIADA'"));
        assertEquals(3,parametros.get(1).get("version"));
        assertEquals(resultado.get("codigo"),parametros.get(2).get("codigo"));
        assertEquals(resultado.get("codigo"),parametros.get(3).get("codigo"));
        assertEquals("Traslado",parametros.get(2).get("servicios"));
        assertEquals("Guía",parametros.get(3).get("servicios"));
        assertEquals(1,parametros.get(2).get("dia"));
        assertEquals(2,parametros.get(3).get("dia"));
        assertTrue(operaciones.stream().noneMatch(sql->sql.contains("DELETE")||sql.startsWith("UPDATE propuesta_detalle")));
    }

    @Test
    @SuppressWarnings("unchecked")
    void historialVinculaElItinerarioCorrectoACadaVersion() {
        var service=new AtencionService();
        service.db=new PortalQuery(){
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){assertEquals(2L,params.get("usuario"));assertEquals(0,params.get("admin"));return new LinkedHashMap<>(Map.of("codigo","SOL-1"));}
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){
                assertEquals("SOL-1",params.get("codigo"));
                if(sql.contains("FROM propuesta_viaje WHERE"))return List.of(new LinkedHashMap<>(Map.of("codigo","PRP-2","version",2)),new LinkedHashMap<>(Map.of("codigo","PRP-1","version",1)));
                if(sql.contains("FROM propuesta_detalle"))return List.of(Map.of("propuesta","PRP-1","dia",1,"descripcion","Original","servicios","Hotel original"),Map.of("propuesta","PRP-2","dia",1,"descripcion","Ajustada","servicios","Hotel nuevo"));
                return List.of();
            }
        };
        var resultado=service.solicitud(asesor,"SOL-1");
        var propuestas=(List<Map<String,Object>>)resultado.get("propuestas");
        var actual=(List<Map<String,Object>>)propuestas.getFirst().get("itinerario");
        var anterior=(List<Map<String,Object>>)propuestas.get(1).get("itinerario");
        assertEquals("Hotel nuevo",actual.getFirst().get("servicios"));
        assertEquals("Hotel original",anterior.getFirst().get("servicios"));
    }

    @Test
    void solicitudAjenaNoExponeVersionesNiDetalles() {
        var service=new AtencionService();
        service.db=new PortalQuery(){
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){assertEquals(2L,params.get("usuario"));return Map.of();}
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){fail("No debe consultar el contenido de una solicitud ajena");return List.of();}
        };
        assertThrows(NotFoundException.class,()->service.solicitud(asesor,"SOL-AJENA"));
    }
}
