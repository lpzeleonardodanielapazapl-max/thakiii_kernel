package com.thakivoyage.resource;

import java.util.List;

import com.thakivoyage.dto.request.DepartamentoRequest;
import com.thakivoyage.service.GestionValidationService;
import com.thakivoyage.entity.Departamento;
import com.thakivoyage.repository.DepartamentoRepository;

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

@Path("/api/v1/departamentos")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class DepartamentoResource {
    @Inject
    GestionValidationService validacion;

    @Inject
    DepartamentoRepository departamentoRepository;

    @GET
    public List<Departamento> listar() {
        return departamentoRepository.listAll();
    }

    @GET
    @Path("/{codigo}")
    public Response obtener(@PathParam("codigo") String codigo) {
        Departamento dep = departamentoRepository.find("codDep", codigo).firstResult();
        if (dep == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Departamento no encontrado\"}")
                .build();
        }
        return Response.ok(dep).build();
    }

    @POST
    @Transactional
    public Response crear(@Valid DepartamentoRequest request) {
        validacion.validar(request, null);
        Departamento existente = departamentoRepository.find("desDep", request.desDep).firstResult();
        if (existente != null) {
            return Response.status(Response.Status.CONFLICT)
                .entity("{\"error\":\"Ya existe un departamento con ese nombre\"}")
                .build();
        }
        String nuevoCodigo = GestionValidationService.codigo("DEP");

        Departamento nuevo = new Departamento();
        nuevo.codDep = nuevoCodigo;
        nuevo.desDep = request.desDep;
        nuevo.descripcion = request.descripcion;

        departamentoRepository.persist(nuevo);

        return Response.status(Response.Status.CREATED).entity(nuevo).build();
    }

    @PUT
    @Path("/{codigo}")
    @Transactional
    public Response actualizar(@PathParam("codigo") String codigo, @Valid DepartamentoRequest request) {
        validacion.validar(request, codigo);
        Departamento dep = departamentoRepository.find("codDep", codigo).firstResult();
        if (dep == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Departamento no encontrado\"}")
                .build();
        }

        dep.desDep = request.desDep;
        dep.descripcion = request.descripcion;
        departamentoRepository.persist(dep);

        return Response.ok(dep).build();
    }

    @DELETE
    @Path("/{codigo}")
    @Transactional
    public Response eliminar(@PathParam("codigo") String codigo) {
        long eliminados = departamentoRepository.delete("codDep", codigo);
        if (eliminados == 0) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Departamento no encontrado\"}")
                .build();
        }
        return Response.noContent().build();
    }
}
