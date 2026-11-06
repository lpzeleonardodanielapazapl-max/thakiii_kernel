package com.thakivoyage.resource;

import java.util.*;
import com.thakivoyage.service.PortalQuery;
import jakarta.inject.Inject;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;

@Path("/api/publico")
@Produces(MediaType.APPLICATION_JSON)
public class PublicoResource {
    @Inject PortalQuery db;

    @GET @Path("/inicio")
    public Map<String,Object> inicio() {
        return PortalQuery.valores("empresa",db.primero("SELECT nombre,slogan,historia,direccion,ciudad,pais,telefono,email FROM empresa ORDER BY cod_empresa LIMIT 1",Map.of(),"nombre","slogan","historia","direccion","ciudad","pais","telefono","email"),
            "destinos",db.lista("""
                SELECT d.cod_destino,d.nombre,d.slug,d.region,d.descripcion_corta,d.dificultad,d.duracion_recomendada_dias,
                    (SELECT i.url FROM destino_imagen i WHERE i.cod_destino=d.cod_destino ORDER BY i.es_principal DESC,i.orden,i.cod_imagen LIMIT 1)
                FROM destino d WHERE d.estado='ACTIVO' ORDER BY d.nombre
                """,Map.of(),"codigo","nombre","slug","region","descripcion","dificultad","dias","imagen"),
            "testimonios",db.lista("SELECT nombre,contenido,calificacion FROM testimonio WHERE publicado=1 ORDER BY fecha DESC,cod_testimonio DESC LIMIT 6",Map.of(),"nombre","contenido","calificacion"));
    }

    @GET @Path("/destinos/{codigo}")
    public Map<String,Object> destino(@PathParam("codigo") String codigo) {
        var destino=db.primero("SELECT cod_destino,nombre,region,descripcion_corta,historia,recomendaciones,como_se_hace,dificultad,altitud_msnm,temporada FROM destino WHERE cod_destino=:codigo AND estado='ACTIVO'",Map.of("codigo",codigo),"codigo","nombre","region","descripcion","historia","recomendaciones","experiencia","dificultad","altitud","temporada");
        if(destino.isEmpty())throw new NotFoundException("Destino no encontrado.");
        return destino;
    }
}
