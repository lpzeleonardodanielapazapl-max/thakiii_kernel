package com.thakivoyage.resource;

import java.sql.SQLException;
import java.util.Map;
import jakarta.ws.rs.WebApplicationException;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.ext.ExceptionMapper;
import jakarta.ws.rs.ext.Provider;
import org.jboss.logging.Logger;

@Provider
public class GestionExceptionMapper implements ExceptionMapper<Exception> {
    @Override
    public Response toResponse(Exception exception) {
        for (Throwable cause = exception; cause != null; cause = cause.getCause()) {
            if (cause instanceof SQLException sql) {
                if (sql.getErrorCode() == 1062) return error(409, "Ya existe un registro con ese documento, correo, nombre o código.");
                if (sql.getErrorCode() == 1451) return error(409, "Este registro tiene información vinculada y no puede eliminarse. Conserva su historial o cambia su estado a inactivo.");
                if (sql.getErrorCode() == 1452) return error(422, "Una de las referencias seleccionadas ya no existe. Actualiza el formulario.");
            }
        }
        if (exception instanceof WebApplicationException web) {
            int status = web.getResponse().getStatus();
            return error(status == 400 ? 422 : status, web.getMessage());
        }
        Logger.getLogger(GestionExceptionMapper.class).error("No se pudo completar la operación.", exception);
        return error(500, "No se pudo completar la operación. Revisa el registro antes de volver a enviarlo.");
    }

    private Response error(int status, String message) {
        return Response.status(status).type(MediaType.APPLICATION_JSON).entity(Map.of("mensaje", message)).build();
    }
}
