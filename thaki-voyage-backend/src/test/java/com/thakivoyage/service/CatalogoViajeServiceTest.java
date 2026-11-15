package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.time.*;
import java.util.*;
import jakarta.ws.rs.*;
import org.junit.jupiter.api.Test;

class CatalogoViajeServiceTest {
    private Map<String,Object> hotel() { return new LinkedHashMap<>(Map.of("nombre"," Hotel de prueba ","ciudad","La Paz","pais","Bolivia","categoria",3,"direccion","Centro","estado","ACTIVO")); }
    private CatalogoViajeService servicio() {
        var service=new CatalogoViajeService();
        service.db=new PortalQuery(){
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){return Map.of("codigo","REF-1","estado","ACTIVO","conductor",true);}
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){return List.of();}
        };
        return service;
    }

    @Test void hotelNormalizaCamposYValidaCategoriaEstadoCorreoYEnlaces() {
        var service=servicio(); var datos=hotel(); datos.put("checkIn","14:00");
        var resultado=service.validar("hoteles",datos,Map.of());
        assertEquals("Hotel de prueba",resultado.get("nombre")); assertEquals(LocalTime.of(14,0),resultado.get("checkIn"));
        for(var cambio:List.of(Map.of("categoria",6),Map.of("estado","PUBLICADO"),Map.of("email","correo-invalido"),Map.of("imagenUrl","javascript:alert(1)"))) {
            var invalido=hotel(); invalido.putAll(cambio); assertThrows(BadRequestException.class,()->service.validar("hoteles",invalido,Map.of()));
        }
    }

    @Test void altaUsaTablaPermitidaYParametrosSinAceptarColumnasAdicionales() {
        var service=servicio(); List<String> operaciones=new ArrayList<>();
        service.db=new PortalQuery(){
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){return Map.of("codigo",params.get("codigo"));}
            @Override public int ejecutar(String sql,Map<String,?> params){operaciones.add(sql);assertFalse(params.containsKey("tabla"));assertEquals("Hotel de prueba",params.get("nombre"));assertTrue(params.get("codigo").toString().startsWith("HOT-"));return 1;}
        };
        var datos=hotel();datos.put("tabla","usuario");
        service.guardar("hoteles",null,datos);
        assertTrue(operaciones.getFirst().startsWith("INSERT INTO hotel (cod_hotel,"));
        assertThrows(NotFoundException.class,()->service.listar("hotel; DELETE FROM usuario"));
    }

    @Test void referenciasInexistentesYReasignacionDeHabitacionSeRechazan() {
        var service=servicio(); var datos=new LinkedHashMap<String,Object>(Map.of("codHotel","HOT-1","nombre","Doble","capacidad",2,"numCamas",1,"estado","ACTIVO"));
        assertThrows(BadRequestException.class,()->service.validar("habitaciones",datos,Map.of("codHotel","HOT-OTRO")));
        service.db=new PortalQuery(){@Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){return Map.of();}};
        assertThrows(BadRequestException.class,()->service.validar("habitaciones",datos,Map.of()));
    }

    @Test void conductorActivoRequiereFuncionLaboralYLicenciaVigente() {
        var service=servicio();
        var datos=new LinkedHashMap<String,Object>(Map.of("codEmpleado","EMP-1","licencia","LIC-1","categoriaLic","C","vencimientoLic",LocalDate.now().plusYears(1).toString(),"experienciaAnios",4,"estado","ACTIVO"));
        assertEquals("EMP-1",service.validar("conductores",datos,Map.of()).get("codEmpleado"));
        datos.put("vencimientoLic",LocalDate.now().minusDays(1).toString());
        assertThrows(BadRequestException.class,()->service.validar("conductores",datos,Map.of()));
        datos.put("vencimientoLic",LocalDate.now().plusYears(1).toString());
        service.db=new PortalQuery(){@Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){return Map.of("codigo","EMP-1","estado","ACTIVO","conductor",0);}};
        assertThrows(BadRequestException.class,()->service.validar("conductores",datos,Map.of()));
    }

    @Test void tarifaRechazaImporteNegativoFechasInvertidasYSolapamientosSinEscribir() {
        var service=servicio(); var datos=new LinkedHashMap<String,Object>(Map.of("codTipoHab","HAB-1","fechaInicio","2027-01-01","fechaFin","2027-01-31","precioNoche","100.00","moneda","BOB","incluyeDesayuno",true));
        assertEquals(1,service.validar("tarifas-hotel",datos,Map.of()).get("incluyeDesayuno"));
        datos.put("precioNoche","-5"); assertThrows(BadRequestException.class,()->service.validar("tarifas-hotel",datos,Map.of()));
        datos.put("precioNoche","100.00");datos.put("fechaFin","2026-12-31");assertThrows(BadRequestException.class,()->service.validar("tarifas-hotel",datos,Map.of()));datos.put("fechaFin","2027-01-31");
        service.db=new PortalQuery(){
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... cols){return Map.of("codigo","HAB-1");}
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){return sql.contains("FOR UPDATE")?List.of():List.of(Map.of("codigo","TAR-1"));}
            @Override public int ejecutar(String sql,Map<String,?> params){fail("No debe guardar tarifas solapadas");return 0;}
        };
        assertEquals(409,assertThrows(ClientErrorException.class,()->service.guardar("tarifas-hotel",null,datos)).getResponse().getStatus());
    }

    @Test void tipoDePreferenciaNoAdmiteValoresFueraDelFormulario() {
        assertEquals("Privado",PortalService.tipoPreferencia("Privado",Set.of("Privado","Compartido")));
        assertNull(PortalService.tipoPreferencia("",Set.of("Privado")));
        assertThrows(BadRequestException.class,()->PortalService.tipoPreferencia("NO_EXISTE",Set.of("Privado")));
    }

    @Test void solicitudConservaAmbosTiposEnLaTablaDePreferenciasExistente() {
        var service=new PortalService(){@Override public Map<String,Object> asignar(AccesoService.Identidad user){return Map.of();}};
        List<Map<String,?>> preferencias=new ArrayList<>();
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... cols){
                if(sql.contains("FROM persona")) return List.of(Map.of("codigo","PER-1","nombres","Ana","apellidos","Pérez","email","ana@example.test","telefono","76543210","direccion","La Paz","tipoDocumento","CI","numeroDocumento","ABC123","fechaNacimiento","1990-01-01"));
                return List.of(Map.of("codigo","CLI-1","estado","ACTIVO"));
            }
            @Override public int ejecutar(String sql,Map<String,?> params){if(sql.contains("solicitud_preferencia")) preferencias.add(new LinkedHashMap<>(params));return 1;}
        };
        service.solicitar(new AccesoService.Identidad(7,"ana@example.test","Ana","CLIENTE"),new PortalService.Viaje(LocalDate.now().plusDays(10),LocalDate.now().plusDays(12),2,null,"BOB","La Paz","Cultura",null,null,null,null,null,null,"Superior / 4 estrellas","Privado"));
        assertTrue(preferencias.stream().anyMatch(p->p.get("tipo").equals("HOTEL_TIPO") && p.get("valor").equals("Superior / 4 estrellas")));
        assertTrue(preferencias.stream().anyMatch(p->p.get("tipo").equals("TRANSPORTE_TIPO") && p.get("valor").equals("Privado")));
    }
}
