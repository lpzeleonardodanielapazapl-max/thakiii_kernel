package com.thakivoyage.resource;

import java.time.LocalDateTime;
import java.util.List;

import com.thakivoyage.dto.request.EmpleadoRequest;
import com.thakivoyage.service.GestionValidationService;
import com.thakivoyage.entity.Empleado;
import com.thakivoyage.repository.EmpleadoRepository;

import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.validation.Valid;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.DELETE;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.PUT;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.PathParam;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("/api/v1/empleados")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class EmpleadoResource {
    @Inject
    GestionValidationService validacion;

    @Inject
    EmpleadoRepository empleadoRepository;

    @GET
    public List<Empleado> listar() {
        return empleadoRepository.listAll();
    }

    @GET
    @Path("/{codigo}")
    public Response obtener(@PathParam("codigo") String codigo) {
        Empleado emp = empleadoRepository.find("codEmpleado", codigo).firstResult();
        if (emp == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Empleado no encontrado\"}")
                .build();
        }
        return Response.ok(emp).build();
    }

    @POST
    @Transactional
    public Response crear(@Valid EmpleadoRequest request) {
        validacion.validar(request, null);
        Empleado existente = empleadoRepository.find("codPersona", request.codPersona).firstResult();
        if (existente != null) {
            return Response.status(Response.Status.CONFLICT)
                .entity("{\"error\":\"Esa persona ya es empleado\"}")
                .build();
        }
        String nuevoCodigo = GestionValidationService.codigo("EMP");

        Empleado nuevo = new Empleado();
        nuevo.codEmpleado = nuevoCodigo;
        nuevo.codPersona = request.codPersona;
        nuevo.codCargo = request.codCargo;
        nuevo.codDep = request.codDep;
        nuevo.fechaIngreso = request.fechaIngreso;
        nuevo.fechaRetiro = request.fechaRetiro;
        nuevo.salario = request.salario;
        nuevo.esGuia = request.esGuia;
        nuevo.esAsesor = request.esAsesor;
        nuevo.esConductor = request.esConductor;
        nuevo.estado = request.estado;
        nuevo.createdAt = LocalDateTime.now();
        nuevo.updatedAt = LocalDateTime.now();

        empleadoRepository.persist(nuevo);

        return Response.status(Response.Status.CREATED).entity(nuevo).build();
    }

    @PUT
    @Path("/{codigo}")
    @Transactional
    public Response actualizar(@PathParam("codigo") String codigo, @Valid EmpleadoRequest request) {
        validacion.validar(request, codigo);
        Empleado emp = empleadoRepository.find("codEmpleado", codigo).firstResult();
        if (emp == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Empleado no encontrado\"}")
                .build();
        }

        emp.codCargo = request.codCargo;
        emp.codDep = request.codDep;
        emp.fechaIngreso = request.fechaIngreso;
        emp.fechaRetiro = request.fechaRetiro;
        emp.salario = request.salario;
        emp.esGuia = request.esGuia;
        emp.esAsesor = request.esAsesor;
        emp.esConductor = request.esConductor;
        emp.estado = request.estado;
        emp.updatedAt = LocalDateTime.now();

        empleadoRepository.persist(emp);

        return Response.ok(emp).build();
    }

    @DELETE
    @Path("/{codigo}")
    @Transactional
    public Response eliminar(@PathParam("codigo") String codigo) {
        long eliminados = empleadoRepository.delete("codEmpleado", codigo);
        if (eliminados == 0) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Empleado no encontrado\"}")
                .build();
        }
        return Response.noContent().build();
    }
}
