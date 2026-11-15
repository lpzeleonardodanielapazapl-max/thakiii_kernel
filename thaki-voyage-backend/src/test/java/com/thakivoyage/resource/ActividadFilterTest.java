package com.thakivoyage.resource;

import static org.junit.jupiter.api.Assertions.*;
import java.lang.reflect.Proxy;
import java.util.Map;
import jakarta.ws.rs.container.*;
import jakarta.ws.rs.core.MultivaluedHashMap;
import org.junit.jupiter.api.Test;

class ActividadFilterTest {
    @Test void registraUn404SinRecursoAsociadoSinOcultarElErrorOriginal() {
        var properties = Map.<String,Object>of(SolicitudIdFilter.SOLICITUD_ID, "peticion-404",
            SolicitudIdFilter.INICIO, System.nanoTime());
        var request = (ContainerRequestContext) Proxy.newProxyInstance(getClass().getClassLoader(),
            new Class<?>[]{ContainerRequestContext.class}, (p,m,a) -> switch (m.getName()) {
                case "getProperty" -> properties.get(a[0]);
                case "getMethod" -> "GET";
                default -> null;
            });
        var headers = new MultivaluedHashMap<String,Object>();
        var response = (ContainerResponseContext) Proxy.newProxyInstance(getClass().getClassLoader(),
            new Class<?>[]{ContainerResponseContext.class}, (p,m,a) -> switch (m.getName()) {
                case "getStatus" -> 404;
                case "getHeaders" -> headers;
                default -> null;
            });
        var filter = new ActividadFilter();
        assertDoesNotThrow(() -> filter.filter(request, response));
        assertEquals("peticion-404", headers.getFirst(SolicitudIdFilter.HEADER));
    }
}
