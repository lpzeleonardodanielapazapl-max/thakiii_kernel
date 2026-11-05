package com.thakivoyage.resource;

import java.time.LocalDateTime;
import java.util.List;

import com.thakivoyage.dto.request.PersonaRequest;
import com.thakivoyage.entity.Persona;
import com.thakivoyage.repository.PersonaRepository;

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

@Path("/api/v1/personas")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class PersonaResource {
    @Inject
    PersonaRepository personaRepository;

    @GET
    public List<Persona> listar() {
        return personaRepository.listAll();
    }

    @GET
    @Path("/{codigo}")
    public Response obtener(@PathParam("codigo") String codigo) {
        Persona persona = personaRepository.find("codPersona", codigo).firstResult();
        if (persona == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Persona no encontrada\"}")
                .build();
        }
        return Response.ok(persona).build();
    }

    @POST
    @Transactional
    public Response crear(@Valid PersonaRequest request) {
        // Verificar duplicado de documento
        if (request.numeroDocumento != null) {
            Persona existente = personaRepository
                .find("numeroDocumento", request.numeroDocumento)
                .firstResult();
            if (existente != null) {
                return Response.status(Response.Status.CONFLICT)
                    .entity("{\"error\":\"Ya existe una persona con ese documento\"}")
                    .build();
            }
        }

        long total = personaRepository.count();
        String nuevoCodigo = String.format("PER-%04d", total + 1);

        Persona nueva = new Persona();
        nueva.codPersona = nuevoCodigo;
        nueva.tipoDocumento = request.tipoDocumento;
        nueva.numeroDocumento = request.numeroDocumento;
        nueva.nombres = request.nombres;
        nueva.apellidos = request.apellidos;
        nueva.telefono = request.telefono;
        nueva.email = request.email;
        nueva.direccion = request.direccion;
        nueva.fechaNacimiento = request.fechaNacimiento;
        nueva.genero = request.genero;
        nueva.createdAt = LocalDateTime.now();
        nueva.updatedAt = LocalDateTime.now();

        personaRepository.persist(nueva);

        return Response.status(Response.Status.CREATED).entity(nueva).build();
    }

    @PUT
    @Path("/{codigo}")
    @Transactional
    public Response actualizar(@PathParam("codigo") String codigo, @Valid PersonaRequest request) {
        Persona persona = personaRepository.find("codPersona", codigo).firstResult();
        if (persona == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Persona no encontrada\"}")
                .build();
        }

        persona.tipoDocumento = request.tipoDocumento;
        persona.numeroDocumento = request.numeroDocumento;
        persona.nombres = request.nombres;
        persona.apellidos = request.apellidos;
        persona.telefono = request.telefono;
        persona.email = request.email;
        persona.direccion = request.direccion;
        persona.fechaNacimiento = request.fechaNacimiento;
        persona.genero = request.genero;
        persona.updatedAt = LocalDateTime.now();

        personaRepository.persist(persona);

        return Response.ok(persona).build();
    }

    @DELETE
    @Path("/{codigo}")
    @Transactional
    public Response eliminar(@PathParam("codigo") String codigo) {
        long eliminados = personaRepository.delete("codPersona", codigo);
        if (eliminados == 0) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Persona no encontrada\"}")
                .build();
        }
        return Response.noContent().build();
    }
}
