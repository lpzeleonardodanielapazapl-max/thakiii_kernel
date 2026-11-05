package com.thakivoyage.resource;

import com.thakivoyage.dto.request.ClienteRequest;
import com.thakivoyage.service.GestionValidationService;
import com.thakivoyage.entity.Cliente;
import com.thakivoyage.repository.ClienteRepository;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.validation.Valid;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

import java.time.LocalDateTime;
import java.util.List;

@Path("/api/v1/clientes")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class ClienteResource {
    @Inject
    GestionValidationService validacion;

    @Inject
    ClienteRepository clienteRepository;

    @GET
    public List<Cliente> listar() {
        return clienteRepository.listAll();
    }

    @GET
    @Path("/{codigo}")
    public Response obtener(@PathParam("codigo") String codigo) {
        Cliente cli = clienteRepository.find("codCliente", codigo).firstResult();
        if (cli == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Cliente no encontrado\"}")
                .build();
        }
        return Response.ok(cli).build();
    }

    @POST
    @Transactional
    public Response crear(@Valid ClienteRequest request) {
        validacion.validar(request, null);
        Cliente existente = clienteRepository.find("codPersona", request.codPersona).firstResult();
        if (existente != null) {
            return Response.status(Response.Status.CONFLICT)
                .entity("{\"error\":\"Esa persona ya es cliente\"}")
                .build();
        }
        String nuevoCodigo = GestionValidationService.codigo("CLI");

        Cliente nuevo = new Cliente();
        nuevo.codCliente = nuevoCodigo;
        nuevo.codPersona = request.codPersona;
        nuevo.codigoCliente = request.codigoCliente;
        nuevo.estado = request.estado;
        nuevo.createdAt = LocalDateTime.now();

        clienteRepository.persist(nuevo);

        return Response.status(Response.Status.CREATED).entity(nuevo).build();
    }

    @PUT
    @Path("/{codigo}")
    @Transactional
    public Response actualizar(@PathParam("codigo") String codigo, @Valid ClienteRequest request) {
        validacion.validar(request, codigo);
        Cliente cli = clienteRepository.find("codCliente", codigo).firstResult();
        if (cli == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Cliente no encontrado\"}")
                .build();
        }

        cli.codigoCliente = request.codigoCliente;
        cli.estado = request.estado;
        clienteRepository.persist(cli);

        return Response.ok(cli).build();
    }

    @DELETE
    @Path("/{codigo}")
    @Transactional
    public Response eliminar(@PathParam("codigo") String codigo) {
        long eliminados = clienteRepository.delete("codCliente", codigo);
        if (eliminados == 0) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Cliente no encontrado\"}")
                .build();
        }
        return Response.noContent().build();
    }
}
