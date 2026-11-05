package com.thakivoyage.resource;

import java.util.List;

import com.thakivoyage.dto.request.RolRequest;
import com.thakivoyage.service.GestionValidationService;
import com.thakivoyage.entity.Rol;
import com.thakivoyage.repository.RolRepository;

import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.validation.Valid;
import jakarta.ws.rs.DELETE;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.PUT;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.PathParam;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("/api/v1/roles")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class RolResource {
    @Inject
    GestionValidationService validacion;

    @Inject
    RolRepository rolRepository;

    @GET
    public List<Rol> listar() {
        return rolRepository.listAll();
    }

    @GET
    @Path("/{codigo}")
    public Response obtener(@PathParam("codigo") String codigo) {
        Rol rol = rolRepository.find("codRol", codigo).firstResult();
        if (rol == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Rol no encontrado\"}")
                .build();
        }
        return Response.ok(rol).build();
    }

    @POST
    @Transactional
    public Response crear(@Valid RolRequest request) {
        return Response.status(405).entity(java.util.Map.of("mensaje", "El sistema utiliza únicamente ADMIN, ADMINISTRATIVO y CLIENTE.")).build();
    }

    @PUT
    @Path("/{codigo}")
    @Transactional
    public Response actualizar(@PathParam("codigo") String codigo, @Valid RolRequest request) {
        validacion.validar(request, codigo);
        Rol rol = rolRepository.find("codRol", codigo).firstResult();
        if (rol == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Rol no encontrado\"}")
                .build();
        }

        rol.desRol = request.desRol;
        rol.descripcion = request.descripcion;

        rolRepository.persist(rol);

        return Response.ok(rol).build();
    }

    @DELETE
    @Path("/{codigo}")
    @Transactional
    public Response eliminar(@PathParam("codigo") String codigo) {
        return Response.status(405).entity(java.util.Map.of("mensaje", "Los roles del sistema no pueden eliminarse.")).build();
    }
}
