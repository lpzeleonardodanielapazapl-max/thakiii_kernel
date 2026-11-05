package com.thakivoyage.resource;

import java.time.LocalDateTime;
import java.util.List;

import com.thakivoyage.dto.request.UsuarioRequest;
import com.thakivoyage.entity.Usuario;
import com.thakivoyage.repository.UsuarioRepository;

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

@Path("/api/v1/usuarios")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class UsuarioResource {

    @Inject
    UsuarioRepository usuarioRepository;

    @GET
    public List<Usuario> listar() {
        return usuarioRepository.listAll();
    }

    @GET
    @Path("/{id}")
    public Response obtener(@PathParam("id") Long id) {
        Usuario user = usuarioRepository.findById(id);
        if (user == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Usuario no encontrado\"}")
                .build();
        }
        return Response.ok(user).build();
    }

    @GET
    @Path("/email/{email}")
    public Response obtenerPorEmail(@PathParam("email") String email) {
        Usuario user = usuarioRepository.findByEmail(email);
        if (user == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Usuario no encontrado\"}")
                .build();
        }
        return Response.ok(user).build();
    }

    @POST
    @Transactional
    public Response crear(@Valid UsuarioRequest request) {
        if (usuarioRepository.findByEmail(request.email) != null) {
            return Response.status(Response.Status.CONFLICT)
                .entity("{\"error\":\"Ya existe un usuario con ese email\"}")
                .build();
        }

        Usuario nuevo = new Usuario();
        nuevo.username = request.username;
        nuevo.email = request.email;
        nuevo.passwordHash = "PENDIENTE_HASH_" + request.password;
        nuevo.codRol = request.codRol;
        nuevo.codEmpleado = request.codEmpleado;
        nuevo.codCliente = request.codCliente;
        nuevo.activo = request.activo;
        nuevo.emailVerificado = false;
        nuevo.createdAt = LocalDateTime.now();

        usuarioRepository.persist(nuevo);

        return Response.status(Response.Status.CREATED).entity(nuevo).build();
    }

    @PUT
    @Path("/{id}")
    @Transactional
    public Response actualizar(@PathParam("id") Long id, @Valid UsuarioRequest request) {
        Usuario user = usuarioRepository.findById(id);
        if (user == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Usuario no encontrado\"}")
                .build();
        }

        user.username = request.username;
        user.email = request.email;
        user.codRol = request.codRol;
        user.codEmpleado = request.codEmpleado;
        user.codCliente = request.codCliente;
        user.activo = request.activo;

        usuarioRepository.persist(user);

        return Response.ok(user).build();
    }

    @DELETE
    @Path("/{id}")
    @Transactional
    public Response eliminar(@PathParam("id") Long id) {
        boolean eliminado = usuarioRepository.deleteById(id);
        if (!eliminado) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Usuario no encontrado\"}")
                .build();
        }
        return Response.noContent().build();
    }
}