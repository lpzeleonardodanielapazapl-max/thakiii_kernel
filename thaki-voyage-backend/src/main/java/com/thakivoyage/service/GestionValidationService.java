package com.thakivoyage.service;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.Set;
import java.util.UUID;

import com.thakivoyage.dto.request.*;
import com.thakivoyage.entity.*;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;
import jakarta.ws.rs.BadRequestException;
import jakarta.ws.rs.ClientErrorException;

@ApplicationScoped
public class GestionValidationService {
    @Inject EntityManager em;

    public static String codigo(String prefijo) {
        return prefijo + "-" + UUID.randomUUID().toString().replace("-", "").substring(0, 16).toUpperCase();
    }

    private String texto(String value, String label, int max, boolean required) {
        String result = value == null ? null : value.strip();
        if (result != null && result.isEmpty()) result = null;
        if (required && result == null) throw new BadRequestException(label + " es obligatorio.");
        if (result != null && result.length() > max) throw new BadRequestException(label + " supera " + max + " caracteres.");
        return result;
    }

    private String email(String value, boolean required) {
        String result = texto(value, "El correo", 150, required);
        if (result != null && !result.matches("^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$")) {
            throw new BadRequestException("Introduce un correo válido.");
        }
        return result;
    }

    private String estado(String value) {
        if (!Set.of("ACTIVO", "INACTIVO").contains(value == null ? "" : value)) {
            throw new BadRequestException("El estado debe ser ACTIVO o INACTIVO.");
        }
        return value;
    }

    private void referencia(Class<?> type, String codigo, boolean required, String label) {
        if (codigo == null && !required) return;
        if (codigo == null || em.find(type, codigo) == null) throw new BadRequestException("Selecciona " + label + " existente.");
    }

    private void unico(String entidad, String campo, String value, String clave, Object actual, String label) {
        if (value == null) return;
        var query = em.createQuery("SELECT COUNT(e) FROM " + entidad + " e WHERE e." + campo
            + " = :valor" + (actual == null ? "" : " AND e." + clave + " <> :actual"), Long.class)
            .setParameter("valor", value);
        if (actual != null) query.setParameter("actual", actual);
        if (query.getSingleResult() > 0) throw new ClientErrorException("Ya existe " + label + ".", 409);
    }

    public void validar(PersonaRequest r, String codigo) {
        if (r == null) throw new BadRequestException("Debes enviar los datos de la persona.");
        r.nombres = texto(r.nombres, "El nombre", 100, true);
        r.apellidos = texto(r.apellidos, "El apellido", 100, true);
        r.tipoDocumento = texto(r.tipoDocumento, "El tipo de documento", 20, false);
        r.numeroDocumento = texto(r.numeroDocumento, "El documento", 30, false);
        r.telefono = texto(r.telefono, "El teléfono", 30, false);
        r.email = email(r.email, false);
        r.direccion = texto(r.direccion, "La dirección", 200, false);
        r.genero = texto(r.genero, "El género", 20, false);
        if (r.fechaNacimiento != null && r.fechaNacimiento.isAfter(LocalDate.now())) throw new BadRequestException("La fecha de nacimiento no puede ser futura.");
        unico("Persona", "numeroDocumento", r.numeroDocumento, "codPersona", codigo, "una persona con ese documento");
    }

    public void validar(EmpleadoRequest r, String codigo) {
        if (r == null) throw new BadRequestException("Debes enviar los datos del empleado.");
        r.codPersona = texto(r.codPersona, "La persona", 20, true);
        r.codCargo = texto(r.codCargo, "El cargo", 20, true);
        r.codDep = texto(r.codDep, "El departamento", 20, true);
        referencia(Persona.class, r.codPersona, true, "una persona");
        referencia(Cargo.class, r.codCargo, true, "un cargo");
        referencia(Departamento.class, r.codDep, true, "un departamento");
        if (codigo != null) {
            Empleado actual = em.find(Empleado.class, codigo);
            if (actual != null && !actual.codPersona.equals(r.codPersona)) throw new BadRequestException("La persona vinculada al empleado no puede cambiarse.");
        }
        unico("Empleado", "codPersona", r.codPersona, "codEmpleado", codigo, "un empleado para esa persona");
        if (r.fechaIngreso == null) throw new BadRequestException("La fecha de ingreso es obligatoria.");
        if (r.fechaRetiro != null && r.fechaRetiro.isBefore(r.fechaIngreso)) throw new BadRequestException("La fecha de retiro no puede ser anterior al ingreso.");
        if (r.salario != null && (r.salario.signum() < 0 || r.salario.scale() > 2 || r.salario.compareTo(new BigDecimal("9999999999.99")) > 0)) throw new BadRequestException("El salario debe ser positivo o cero y tener hasta dos decimales.");
        r.estado = estado(r.estado);
        r.esGuia = Boolean.TRUE.equals(r.esGuia);
        r.esAsesor = Boolean.TRUE.equals(r.esAsesor);
        r.esConductor = Boolean.TRUE.equals(r.esConductor);
    }

    public void validar(ClienteRequest r, String codigo) {
        if (r == null) throw new BadRequestException("Debes enviar los datos del cliente.");
        r.codPersona = texto(r.codPersona, "La persona", 20, true);
        referencia(Persona.class, r.codPersona, true, "una persona");
        if (codigo == null) em.find(Persona.class, r.codPersona, jakarta.persistence.LockModeType.PESSIMISTIC_WRITE);
        if (codigo != null) {
            Cliente actual = em.find(Cliente.class, codigo);
            if (actual != null && !actual.codPersona.equals(r.codPersona)) throw new BadRequestException("La persona vinculada al cliente no puede cambiarse.");
        }
        r.codigoCliente = texto(r.codigoCliente, "La referencia del cliente", 30, false);
        r.estado = estado(r.estado);
        unico("Cliente", "codPersona", r.codPersona, "codCliente", codigo, "un cliente para esa persona");
        unico("Cliente", "codigoCliente", r.codigoCliente, "codCliente", codigo, "un cliente con esa referencia");
    }

    public void validar(CargoRequest r, String codigo) {
        if (r == null) throw new BadRequestException("Debes enviar los datos del cargo.");
        r.desCargo = texto(r.desCargo, "El nombre del cargo", 100, true);
        r.descripcion = texto(r.descripcion, "La descripción", 2000, false);
        unico("Cargo", "desCargo", r.desCargo, "codCargo", codigo, "un cargo con ese nombre");
    }

    public void validar(DepartamentoRequest r, String codigo) {
        if (r == null) throw new BadRequestException("Debes enviar los datos del departamento.");
        r.desDep = texto(r.desDep, "El nombre del departamento", 100, true);
        r.descripcion = texto(r.descripcion, "La descripción", 2000, false);
        unico("Departamento", "desDep", r.desDep, "codDep", codigo, "un departamento con ese nombre");
    }

    public void validar(UsuarioRequest r, Long id) {
        if (r == null) throw new BadRequestException("Debes enviar los datos del usuario.");
        r.username = texto(r.username, "El nombre de usuario", 60, false);
        r.email = email(r.email, true);
        r.codRol = texto(r.codRol, "El rol", 20, true);
        referencia(Rol.class, r.codRol, true, "un rol");
        r.activo = Boolean.TRUE.equals(r.activo);
        if (id == null && (r.password == null || r.password.isBlank())) throw new BadRequestException("La contraseña es obligatoria.");
        if (r.password != null && !r.password.isEmpty() && (r.password.length() < 8 || r.password.length() > 255)) throw new BadRequestException("La contraseña debe tener entre 8 y 255 caracteres.");
        unico("Usuario", "email", r.email, "idUsuario", id, "un usuario con ese correo");
        unico("Usuario", "username", r.username, "idUsuario", id, "un usuario con ese nombre");
    }

    public void validar(RolRequest r, String codigo) {
        if (r == null) throw new BadRequestException("Debes enviar los datos del rol.");
        r.desRol = texto(r.desRol, "El nombre del rol", 50, true);
        Rol actual = em.find(Rol.class, codigo);
        if (actual != null && !actual.desRol.equals(r.desRol)) throw new BadRequestException("Los nombres de los roles del sistema no pueden cambiarse.");
        r.descripcion = texto(r.descripcion, "La descripción", 2000, false);
    }
}
