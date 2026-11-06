package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.util.*;
import jakarta.ws.rs.*;
import org.junit.jupiter.api.Test;

class AccesoServiceTest {
    @Test
    void administradorInicialSePersisteConHashYPuedeIngresarConLasMismasCredenciales() {
        var service=new AccesoService();
        service.passwords=new PasswordService();
        var almacenados=new ArrayList<com.thakivoyage.entity.Usuario>();
        service.db=new PortalQuery(){
            @Override public void persistirUsuario(com.thakivoyage.entity.Usuario usuario){usuario.idUsuario=1L;almacenados.add(usuario);}
            @Override public List<com.thakivoyage.entity.Usuario> usuariosPorCorreo(String email){return almacenados.stream().filter(u->u.email.equals(email)).toList();}
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... columnas){return sql.contains("SELECT des_rol")?Map.of("rol","ADMIN"):Map.of("codigo","ROL-001");}
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas){return List.of();}
            @Override public int ejecutar(String sql,Map<String,?> params){assertEquals(64,params.get("token").toString().length());return 1;}
        };
        var inicial=service.inicializar(new AccesoService.Registro("Admin",null,"admin@example.test","clave-de-prueba"));
        assertEquals("ADMIN",inicial.usuario().rol());
        assertNotEquals("clave-de-prueba",almacenados.getFirst().passwordHash);
        assertTrue(service.passwords.matches("clave-de-prueba",almacenados.getFirst().passwordHash));
        var ingreso=service.login(new AccesoService.Credenciales("admin@example.test","clave-de-prueba"));
        assertEquals(inicial.usuario(),ingreso.usuario());
        assertNotEquals(inicial.token(),ingreso.token());
    }
    @Test
    void ultimoAdministradorNoPuedeEliminarseDesactivarseNiPerderSuRol() {
        var service=new AccesoService();
        service.db=new PortalQuery(){
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... columnas){return sql.contains("FROM rol")?Map.of("codigo","ROL-001"):Map.of("rol","ROL-001","activo",true);}
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas){return List.of();}
        };
        assertThrows(ClientErrorException.class,()->service.protegerAdministrador(1,null,false,true));
        assertThrows(ClientErrorException.class,()->service.protegerAdministrador(1,"ROL-001",false,false));
        assertThrows(ClientErrorException.class,()->service.protegerAdministrador(1,"ROL-003",true,false));
        assertDoesNotThrow(()->service.protegerAdministrador(1,"ROL-001",true,false));
    }
    @Test
    void rechazaCabecerasAusentesYTokensConFormatoInvalidoSinConsultarLaBase() {
        var service = new AccesoService();
        for (String header : Arrays.asList(null, "", "Basic abc", "Bearer corto", "Bearer " + "!".repeat(43))) {
            assertThrows(NotAuthorizedException.class, () -> service.autenticar(header));
        }
    }

    @Test
    void consultaSoloElDigestDelTokenYExigeSesionActivaNoVencida() {
        String token = "a".repeat(43);
        var service = new AccesoService();
        service.db = new PortalQuery() {
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... columnas) {
                assertNotEquals(token, params.get("token"));
                assertEquals(AccesoService.digest(token), params.get("token"));
                assertTrue(sql.contains("s.activa=1"));
                assertTrue(sql.contains("u.activo=1"));
                assertTrue(sql.contains("INTERVAL 8 HOUR"));
                return Map.of("id",7,"email","ana@example.test","nombre","Ana","rol","CLIENTE");
            }
        };
        assertEquals(7,service.autenticar("Bearer " + token).id());
    }

    @Test
    void sesionNoEncontradaNoGeneraUnaIdentidad() {
        var service = new AccesoService();
        service.db = new PortalQuery() {
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... columnas) { return Map.of(); }
        };
        assertEquals("La sesión terminó. Vuelve a ingresar.",assertThrows(NotAuthorizedException.class, () -> service.autenticar("Bearer " + "a".repeat(43))).getMessage());
    }

    @Test
    void registroNoSeApropiaDePersonasExistentesPorCoincidenciaDeCorreo() {
        var service = new AccesoService();
        service.db = new PortalQuery() {
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... columnas) { return Map.of("codigo","ROL-003"); }
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas) {
                return sql.contains("FROM persona") ? List.of(Map.of("codigo","PER-OTRA")) : List.of();
            }
            @Override public int ejecutar(String sql,Map<String,?> params) { fail("No debe escribir datos al detectar un correo existente"); return 0; }
        };
        assertEquals(409,assertThrows(ClientErrorException.class, () -> service.registrar(new AccesoService.Registro("Ana","Pérez","ana@example.test","clave-segura"))).getResponse().getStatus());
    }

    @Test
    void primerAdministradorNoPuedeVolverACrearseAunqueLaCuentaEsteInactiva() {
        var service = new AccesoService();
        service.db = new PortalQuery() {
            @Override public Map<String,Object> primero(String sql,Map<String,?> params,String... columnas) { return Map.of("codigo","ROL-001"); }
            @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas) {
                assertFalse(sql.contains("activo=1"));
                return List.of(Map.of("id",1));
            }
        };
        assertEquals(409,assertThrows(ClientErrorException.class, () -> service.inicializar(new AccesoService.Registro("Admin",null,"admin@example.test","clave-segura"))).getResponse().getStatus());
    }
}
