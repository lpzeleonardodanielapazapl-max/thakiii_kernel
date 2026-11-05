package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import java.lang.reflect.Proxy;
import java.math.BigDecimal;
import java.time.LocalDate;
import org.junit.jupiter.api.Test;
import com.thakivoyage.dto.request.*;
import com.thakivoyage.entity.*;
import jakarta.persistence.EntityManager;
import jakarta.persistence.TypedQuery;
import jakarta.ws.rs.BadRequestException;

class GestionValidationServiceTest {
    private GestionValidationService servicio(boolean referencias) {
        GestionValidationService service = new GestionValidationService();
        Object query = Proxy.newProxyInstance(getClass().getClassLoader(), new Class<?>[] {TypedQuery.class},
            (proxy, method, args) -> switch (method.getName()) {
                case "setParameter" -> proxy;
                case "getSingleResult" -> 0L;
                default -> throw new UnsupportedOperationException(method.getName());
            });
        service.em = (EntityManager) Proxy.newProxyInstance(getClass().getClassLoader(), new Class<?>[] {EntityManager.class},
            (proxy, method, args) -> {
                if (method.getName().equals("createQuery")) return query;
                if (method.getName().equals("find")) {
                    if (!referencias) return null;
                    if (args[0] == Empleado.class) { var e = new Empleado(); e.codPersona = "PER-1"; return e; }
                    if (args[0] == Rol.class) { var r = new Rol(); r.desRol = "ADMIN"; return r; }
                    return ((Class<?>) args[0]).getDeclaredConstructor().newInstance();
                }
                throw new UnsupportedOperationException(method.getName());
            });
        return service;
    }

    private EmpleadoRequest empleado() {
        var r = new EmpleadoRequest();
        r.codPersona = "PER-1"; r.codCargo = "CAR-1"; r.codDep = "DEP-1";
        r.fechaIngreso = LocalDate.of(2026, 1, 1); r.estado = "ACTIVO";
        return r;
    }

    @Test void normalizaPersonaYRechazaDatosInvalidos() {
        var r = new PersonaRequest(); r.nombres = " Ana "; r.apellidos = " Pérez ";
        servicio(true).validar(r, null);
        assertEquals("Ana", r.nombres); assertEquals("Pérez", r.apellidos);
        r.email = "correo-invalido";
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
        r.email = null; r.fechaNacimiento = LocalDate.now().plusDays(1);
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
    }

    @Test void empleadoNecesitaPersonaCargoYDepartamentoExistentes() {
        assertThrows(BadRequestException.class, () -> servicio(false).validar(empleado(), null));
        var r = empleado(); r.codCargo = " ";
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
    }

    @Test void empleadoRechazaFechasYSalarioInvalidos() {
        var r = empleado(); r.fechaRetiro = r.fechaIngreso.minusDays(1);
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
        r.fechaRetiro = null; r.salario = new BigDecimal("-1");
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
        r.salario = new BigDecimal("12.345");
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
    }

    @Test void noSePuedeCambiarLaPersonaDeUnEmpleadoExistente() {
        var r = empleado(); r.codPersona = "PER-2";
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, "EMP-1"));
    }

    @Test void funcionesDeEmpleadoSonIndependientesDelRolDeAcceso() {
        var r = empleado(); r.esGuia = true; r.esAsesor = true; r.esConductor = null;
        servicio(true).validar(r, null);
        assertTrue(r.esGuia); assertTrue(r.esAsesor); assertFalse(r.esConductor);
    }

    @Test void usuarioNuevoNecesitaClaveYEditarPuedeConservarla() {
        var r = new UsuarioRequest(); r.email = "ana@example.test"; r.codRol = "ROL-1";
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
        r.password = "corta";
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, null));
        r.password = null;
        assertDoesNotThrow(() -> servicio(true).validar(r, 1L));
    }

    @Test void elNombreDelRolSeConserva() {
        var r = new RolRequest(); r.desRol = "GUIA";
        assertThrows(BadRequestException.class, () -> servicio(true).validar(r, "ROL-1"));
    }
}
