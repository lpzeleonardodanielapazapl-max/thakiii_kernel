package com.thakivoyage.resource;

import java.util.Set;
import java.util.concurrent.TimeUnit;

import com.thakivoyage.service.AccesoService;
import jakarta.ws.rs.container.ContainerRequestContext;
import jakarta.ws.rs.container.ContainerResponseContext;
import jakarta.ws.rs.container.ContainerResponseFilter;
import jakarta.ws.rs.container.ResourceInfo;
import jakarta.ws.rs.core.Context;
import jakarta.ws.rs.ext.Provider;
import org.jboss.logging.Logger;

@Provider
public class ActividadFilter implements ContainerResponseFilter {
    private static final Logger LOG = Logger.getLogger("com.thakivoyage.actividad");
    private static final Set<String> METODOS_DE_CAMBIO = Set.of("POST", "PUT", "PATCH", "DELETE");

    @Context ResourceInfo recurso;

    @Override
    public void filter(ContainerRequestContext request, ContainerResponseContext response) {
        String id = (String) request.getProperty(SolicitudIdFilter.SOLICITUD_ID);
        if (id == null) {
            return;
        }
        response.getHeaders().putSingle(SolicitudIdFilter.HEADER, id);

        int estado = response.getStatus();
        if (estado < 400 && !METODOS_DE_CAMBIO.contains(request.getMethod())) {
            return;
        }

        var metodo = recurso == null ? null : recurso.getResourceMethod();
        var clase = recurso == null ? null : recurso.getResourceClass();
        String operacion = metodo == null || clase == null ? "sin_recurso" : clase.getSimpleName() + "." + metodo.getName();
        var identidad = (AccesoService.Identidad) request.getProperty("thaki.identidad");
        if (response.getEntity() instanceof AccesoService.Sesion sesion) {
            identidad = sesion.usuario();
        }
        long duracion = TimeUnit.NANOSECONDS.toMillis(System.nanoTime() - (long) request.getProperty(SolicitudIdFilter.INICIO));
        Logger.Level nivel = Logger.Level.INFO;
        if (estado >= 500) {
            nivel = Logger.Level.ERROR;
        } else if (estado >= 400) {
            nivel = Logger.Level.WARN;
        }
        LOG.logf(nivel, "api.respuesta solicitud_id=%s operacion=%s metodo=%s estado_http=%d usuario_id=%s rol=%s duracion_ms=%d",
            id, operacion, request.getMethod(), estado, identidad == null ? "-" : identidad.id(),
            identidad == null ? "-" : identidad.rol(), duracion);
    }
}
