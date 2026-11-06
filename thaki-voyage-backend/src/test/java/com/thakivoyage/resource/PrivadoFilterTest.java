package com.thakivoyage.resource;

import static org.junit.jupiter.api.Assertions.*;
import java.lang.reflect.Proxy;
import java.util.concurrent.atomic.AtomicInteger;
import com.thakivoyage.service.AccesoService;
import jakarta.ws.rs.NotAuthorizedException;
import jakarta.ws.rs.container.ContainerRequestContext;
import jakarta.ws.rs.core.*;
import org.junit.jupiter.api.Test;

class PrivadoFilterTest {
    private int estado(String path,String method,String rol) {
        AtomicInteger status=new AtomicInteger(200);
        UriInfo uri=(UriInfo)Proxy.newProxyInstance(getClass().getClassLoader(),new Class<?>[]{UriInfo.class},(p,m,a)->m.getName().equals("getPath")?path:null);
        ContainerRequestContext request=(ContainerRequestContext)Proxy.newProxyInstance(getClass().getClassLoader(),new Class<?>[]{ContainerRequestContext.class},(p,m,a)->{
            return switch(m.getName()) {
                case "getUriInfo" -> uri;
                case "getMethod" -> method;
                case "getHeaderString" -> "Bearer token";
                case "abortWith" -> {status.set(((Response)a[0]).getStatus());yield null;}
                default -> null;
            };
        });
        var filter=new PrivadoFilter();
        filter.acceso=new AccesoService(){@Override public Identidad autenticar(String h){if(rol==null)throw new NotAuthorizedException("Sesión requerida");return new Identidad(7,"ana@example.test","Ana",rol);}};
        filter.filter(request);
        return status.get();
    }

    @Test void accesoAnonimoSoloAPublicoYFormulariosDeAcceso() {
        assertEquals(200,estado("api/publico/inicio","GET",null));
        assertEquals(200,estado("api/acceso/registrar","POST",null));
        assertEquals(401,estado("api/v1/usuarios","GET",null));
        assertEquals(401,estado("api/cliente/resumen","GET",null));
    }

    @Test void clienteNoPuedeLeerNiModificarGestionInterna() {
        assertEquals(200,estado("api/cliente/resumen","GET","CLIENTE"));
        for(String method:new String[]{"GET","POST","PUT","DELETE"})assertEquals(403,estado("api/v1/usuarios/1",method,"CLIENTE"));
        assertEquals(403,estado("api/atencion/panel","GET","CLIENTE"));
        assertEquals(403,estado("api/cliente/perfil","GET","ADMIN"));
    }

    @Test void administrativoNoPuedeElevarPermisosMedianteUsuariosORoles() {
        assertEquals(403,estado("api/v1/usuarios/1","PUT","ADMINISTRATIVO"));
        assertEquals(403,estado("api/v1/roles/ROL-1","DELETE","ADMINISTRATIVO"));
        assertEquals(200,estado("api/v1/usuarios/1","PUT","ADMIN"));
        assertEquals(200,estado("api/atencion/panel","GET","ADMINISTRATIVO"));
    }
}
