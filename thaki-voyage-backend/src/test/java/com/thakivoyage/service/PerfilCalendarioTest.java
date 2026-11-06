package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.time.LocalDate;
import java.util.*;
import jakarta.ws.rs.*;
import org.junit.jupiter.api.Test;

class PerfilCalendarioTest {
    private final AccesoService.Identidad cliente = new AccesoService.Identidad(7,"ana@example.test","Ana","CLIENTE");

    private Map<String,Object> perfilCompleto() {
        return new LinkedHashMap<>(Map.of("codigo","PER-7","nombres","Ana","apellidos","Pérez","email","ana@example.test",
            "telefono","76543210","direccion","La Paz","tipoDocumento","Pasaporte","numeroDocumento","ABC123","fechaNacimiento","1995-03-20"));
    }

    @Test void porcentajeDerivaDeLosDatosGuardadosYElGeneroEsOpcional() {
        var perfil=perfilCompleto();
        assertEquals(true,PortalService.completitud(perfil).get("completo"));
        assertEquals(100,PortalService.completitud(perfil).get("porcentaje"));
        perfil.put("telefono","  ");
        var estado=PortalService.completitud(perfil);
        assertEquals(false,estado.get("completo"));
        assertEquals(87,estado.get("porcentaje"));
        assertEquals(List.of("Teléfono"),estado.get("faltantes"));
        assertEquals(List.of("telefono"),estado.get("camposFaltantes"));
        perfil.put("fechaNacimiento",LocalDate.now().plusDays(1).toString());
        assertTrue(((List<?>)PortalService.completitud(perfil).get("faltantes")).contains("Fecha de nacimiento"));
    }

    @Test void apiNoPermiteSolicitarConPerfilIncompletoNiEscribeDatos() {
        var service=new PortalService();
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas){
                assertTrue(sql.contains("FROM persona"));
                assertEquals(cliente.email(),params.get("email"));
                return List.of(Map.of("codigo","PER-7","nombres","Ana","email",cliente.email()));
            }
            @Override public int ejecutar(String sql,Map<String,?> params){fail("No debe escribir solicitudes ni preferencias");return 0;}
        };
        var error=assertThrows(ClientErrorException.class,()->service.solicitar(cliente,null));
        assertEquals(409,error.getResponse().getStatus());
        assertTrue(error.getMessage().contains("100 %"));
        assertTrue(error.getMessage().contains("Número de documento"));
    }

    @Test void calendarioDelAsesorFiltraAsignacionAceptacionCanceladosYViajesQueCruzanElMes() {
        var service=new AtencionService();
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas){
                assertEquals(0,params.get("admin")); assertEquals(2L,params.get("usuario"));
                assertTrue(sql.contains("a.activa=1 AND a.id_usuario_ase=:usuario"));
                assertTrue(sql.contains("p.estado='ACEPTADA'")); assertTrue(sql.contains("pa.aprobado=1"));
                assertTrue(sql.contains("r.estado IN ('PENDIENTE_ANTICIPO','CONFIRMADA')"));
                assertTrue(sql.contains("r.fecha_inicio<=:hasta AND r.fecha_fin>=:desde"));
                return List.of(Map.of("titulo","Viaje aceptado"));
            }
        };
        var asesor=new AccesoService.Identidad(2,"asesor@example.test","Asesor","ADMINISTRATIVO");
        assertEquals(1,service.calendario(asesor,LocalDate.of(2026,10,26),LocalDate.of(2026,12,6)).size());
    }

    @Test void calendarioAdminSupervisaTodosYRechazaRangosExcesivosAntesDeConsultar() {
        var service=new AtencionService();
        service.db=new PortalQuery(){
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas){assertEquals(1,params.get("admin"));return List.of();}
        };
        var admin=new AccesoService.Identidad(1,"admin@example.test","Admin","ADMIN");
        assertTrue(service.calendario(admin,LocalDate.of(2026,11,1),LocalDate.of(2026,11,30)).isEmpty());
        assertThrows(BadRequestException.class,()->service.calendario(admin,LocalDate.of(2026,1,1),LocalDate.of(2026,12,31)));
        assertThrows(BadRequestException.class,()->service.calendario(admin,null,LocalDate.now()));
        assertThrows(BadRequestException.class,()->service.calendario(admin,LocalDate.now(),LocalDate.now().minusDays(1)));
    }
}
