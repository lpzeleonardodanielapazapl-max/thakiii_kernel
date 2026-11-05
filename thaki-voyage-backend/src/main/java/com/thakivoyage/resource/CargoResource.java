package com.thakivoyage.resource;

import java.util.List;

import com.thakivoyage.dto.request.CargoRequest;
import com.thakivoyage.entity.Cargo;
import com.thakivoyage.repository.CargoRepository;

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

@Path("/api/v1/cargos")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class CargoResource {

    @Inject
    CargoRepository cargoRepository;

    @GET
    public List<Cargo> listar() {
        return cargoRepository.listAll();
    }

    @GET
    @Path("/{codigo}")
    public Response obtener(@PathParam("codigo") String codigo) {
        Cargo cargo = cargoRepository.find("codCargo", codigo).firstResult();
        if (cargo == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Cargo no encontrado\"}")
                .build();
        }
        return Response.ok(cargo).build();
    }

    @POST
    @Transactional
    public Response crear(@Valid CargoRequest request) {
        Cargo existente = cargoRepository.find("desCargo", request.desCargo).firstResult();
        if (existente != null) {
            return Response.status(Response.Status.CONFLICT)
                .entity("{\"error\":\"Ya existe un cargo con ese nombre\"}")
                .build();
        }

        long total = cargoRepository.count();
        String nuevoCodigo = String.format("CAR-%03d", total + 1);

        Cargo nuevo = new Cargo();
        nuevo.codCargo = nuevoCodigo;
        nuevo.desCargo = request.desCargo;
        nuevo.descripcion = request.descripcion;

        cargoRepository.persist(nuevo);

        return Response.status(Response.Status.CREATED).entity(nuevo).build();
    }

    @PUT
    @Path("/{codigo}")
    @Transactional
    public Response actualizar(@PathParam("codigo") String codigo, @Valid CargoRequest request) {
        Cargo cargo = cargoRepository.find("codCargo", codigo).firstResult();
        if (cargo == null) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Cargo no encontrado\"}")
                .build();
        }

        cargo.desCargo = request.desCargo;
        cargo.descripcion = request.descripcion;
        cargoRepository.persist(cargo);

        return Response.ok(cargo).build();
    }

    @DELETE
    @Path("/{codigo}")
    @Transactional
    public Response eliminar(@PathParam("codigo") String codigo) {
        long eliminados = cargoRepository.delete("codCargo", codigo);
        if (eliminados == 0) {
            return Response.status(Response.Status.NOT_FOUND)
                .entity("{\"error\":\"Cargo no encontrado\"}")
                .build();
        }
        return Response.noContent().build();
    }
}