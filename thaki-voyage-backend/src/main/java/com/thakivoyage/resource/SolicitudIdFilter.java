package com.thakivoyage.resource;

import java.util.UUID;
import java.util.regex.Pattern;

import jakarta.ws.rs.container.ContainerRequestContext;
import jakarta.ws.rs.container.ContainerRequestFilter;
import jakarta.ws.rs.container.PreMatching;
import jakarta.ws.rs.ext.Provider;

@Provider
@PreMatching
public class SolicitudIdFilter implements ContainerRequestFilter {
    static final String HEADER = "X-Request-ID";
    static final String SOLICITUD_ID = "thaki.solicitud_id";
    static final String INICIO = "thaki.inicio_solicitud";
    private static final Pattern UUID_VALIDO = Pattern.compile("(?i)[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}");

    @Override
    public void filter(ContainerRequestContext request) {
        String path = request.getUriInfo().getPath();
        if (!path.startsWith("api/") && !path.startsWith("/api/")) {
            return;
        }

        String recibido = request.getHeaderString(HEADER);
        String id = recibido != null && UUID_VALIDO.matcher(recibido).matches() ? recibido : UUID.randomUUID().toString();
        request.setProperty(SOLICITUD_ID, id);
        request.setProperty(INICIO, System.nanoTime());
    }
}
