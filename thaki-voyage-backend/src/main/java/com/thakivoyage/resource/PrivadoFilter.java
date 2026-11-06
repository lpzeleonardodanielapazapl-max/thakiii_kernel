package com.thakivoyage.resource;

import java.util.Map;
import java.util.Set;
import com.thakivoyage.service.AccesoService;
import jakarta.annotation.Priority;
import jakarta.inject.Inject;
import jakarta.ws.rs.Priorities;
import jakarta.ws.rs.WebApplicationException;
import jakarta.ws.rs.container.ContainerRequestContext;
import jakarta.ws.rs.container.ContainerRequestFilter;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.ext.Provider;

@Provider
@Priority(Priorities.AUTHENTICATION)
public class PrivadoFilter implements ContainerRequestFilter {
    @Inject AccesoService acceso;

    @Override
    public void filter(ContainerRequestContext request) {
        String path = request.getUriInfo().getPath().replaceFirst("^/", "");
        if (!path.startsWith("api/") || path.startsWith("api/publico/")
            || Set.of("api/acceso/ingresar", "api/acceso/registrar", "api/acceso/configuracion", "api/acceso/inicializar", "api/docs").contains(path)
            || request.getMethod().equals("OPTIONS")) return;
        try {
            var identity = acceso.autenticar(request.getHeaderString("Authorization"));
            boolean cliente = identity.rol().equals("CLIENTE");
            if (path.startsWith("api/cliente/")) {
                if (!cliente) throw new jakarta.ws.rs.ForbiddenException("Este espacio corresponde a clientes.");
            } else if (!path.startsWith("api/acceso/") && cliente) {
                throw new jakarta.ws.rs.ForbiddenException("No tienes acceso a la gestión interna.");
            }
            if (!request.getMethod().equals("GET") && (path.startsWith("api/v1/usuarios") || path.startsWith("api/v1/roles")) && !identity.rol().equals("ADMIN")) {
                throw new jakarta.ws.rs.ForbiddenException("Esta operación requiere un administrador.");
            }
            request.setProperty("thaki.identidad", identity);
        } catch (WebApplicationException e) {
            request.abortWith(Response.status(e.getResponse().getStatus()).type(MediaType.APPLICATION_JSON).entity(Map.of("mensaje", e.getMessage())).build());
        }
    }
}
