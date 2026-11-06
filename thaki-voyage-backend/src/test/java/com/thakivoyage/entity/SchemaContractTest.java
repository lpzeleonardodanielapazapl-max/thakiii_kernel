package com.thakivoyage.entity;

import static org.junit.jupiter.api.Assertions.*;

import java.nio.file.Files;
import java.nio.file.Path;
import java.util.regex.Pattern;

import org.junit.jupiter.api.Test;

import com.fasterxml.jackson.databind.ObjectMapper;

import jakarta.persistence.Column;
import jakarta.persistence.Table;

class SchemaContractTest {
    @Test
    void identificadorDeUsuarioRespetaElTipoIntDeLaBaseExistente() throws Exception {
        assertEquals(org.hibernate.type.SqlTypes.INTEGER,Usuario.class.getDeclaredField("idUsuario")
            .getAnnotation(org.hibernate.annotations.JdbcTypeCode.class).value());
    }
    @Test
    void losDirectoriosSoloMapeanColumnasDelEsquemaExistente() throws Exception {
        String sql = Files.readString(Path.of("..", "thaki.sql"));
        for (Class<?> entidad : new Class<?>[] {
            Persona.class, Empleado.class, Cliente.class, Usuario.class, Rol.class,
            Cargo.class, Departamento.class
        }) {
            String tabla = entidad.getAnnotation(Table.class).name();
            var bloque = Pattern.compile("CREATE TABLE " + Pattern.quote(tabla)
                + " \\((.*?)\\) ENGINE", Pattern.DOTALL).matcher(sql);
            assertTrue(bloque.find(), "La tabla no existe: " + tabla);
            String columnas = bloque.group(1);
            for (var campo : entidad.getDeclaredFields()) {
                Column columna = campo.getAnnotation(Column.class);
                if (columna != null) {
                    assertTrue(Pattern.compile("^\\s*" + Pattern.quote(columna.name()) + "\\s+",
                        Pattern.MULTILINE).matcher(columnas).find(),
                        tabla + "." + columna.name() + " no existe en thaki.sql");
                }
            }
        }
    }

    @Test
    void lasRespuestasDeUsuarioNoExponenLaContrasena() throws Exception {
        Usuario usuario = new Usuario();
        usuario.email = "ana@example.test";
        usuario.passwordHash = "dato-privado-de-prueba";
        String json = new ObjectMapper().writeValueAsString(usuario);
        assertTrue(json.contains("ana@example.test"));
        assertFalse(json.contains("passwordHash"));
        assertFalse(json.contains("dato-privado-de-prueba"));
    }
}
