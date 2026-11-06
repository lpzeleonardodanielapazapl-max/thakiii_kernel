package com.thakivoyage.resource;

import java.util.Map;
import com.thakivoyage.service.AccesoService;
import io.quarkus.runtime.LaunchMode;
import io.vertx.ext.web.RoutingContext;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;

@Path("/api/acceso")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class AccesoResource {
    @Inject AccesoService servicio;
    @Inject RoutingContext context;

    private boolean local() {
        String host = context.request().remoteAddress().host();
        return LaunchMode.current() == LaunchMode.DEVELOPMENT && java.util.Set.of("127.0.0.1", "::1", "0:0:0:0:0:0:0:1").contains(host);
    }

    @GET @Path("/configuracion")
    public Map<String, Boolean> configuracion() {
        return Map.of("configurarAdministrador", local() && servicio.necesitaAdministrador());
    }

    @POST @Path("/inicializar")
    public AccesoService.Sesion inicializar(AccesoService.Registro datos) {
        if (!local()) throw new NotFoundException();
        return servicio.inicializar(datos);
    }

    @POST @Path("/ingresar")
    public AccesoService.Sesion ingresar(AccesoService.Credenciales datos) { return servicio.login(datos); }

    @POST @Path("/registrar")
    public AccesoService.Sesion registrar(AccesoService.Registro datos) { return servicio.registrar(datos); }

    @GET @Path("/sesion")
    public AccesoService.Identidad sesion(@HeaderParam("Authorization") String header) { return servicio.autenticar(header); }

    @POST @Path("/salir")
    public void salir(@HeaderParam("Authorization") String header) { servicio.salir(header); }
}
