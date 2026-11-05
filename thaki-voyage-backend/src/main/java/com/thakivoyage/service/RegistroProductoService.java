package com.thakivoyage.service;

import java.util.List;
import java.util.Map;
import java.util.UUID;

import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.BadRequestException;

@ApplicationScoped
public class RegistroProductoService {

    @Inject
    EntityManager entityManager;

    public record Opcion(String codigo, String nombre) {}

    public record ProductoRequest(
        String nombre,
        String codClase,
        String codMarca,
        String codUm,
        String modelo,
        String color
    ) {}

    public record ProductoCreado(String codigo, String nombre) {}

    public Map<String, List<Opcion>> catalogos() {
        return Map.of(
            "clases", opciones("""
                SELECT cod_clase, des_clase
                FROM clase_producto
                ORDER BY des_clase
                """),

            "marcas", opciones("""
                SELECT cod_mar, des_mar
                FROM marca
                ORDER BY des_mar
                """),

            "unidadesMedida", opciones("""
                SELECT cod_um, des_um
                FROM unidad_medida
                ORDER BY des_um
                """)
        );
    }

    private List<Opcion> opciones(String sql) {
        List<?> filas = entityManager
            .createNativeQuery(sql)
            .getResultList();

        return filas.stream()
            .map(fila -> {
                Object[] valores = (Object[]) fila;

                return new Opcion(
                    (String) valores[0],
                    (String) valores[1]
                );
            })
            .toList();
    }

    private String texto(
        String valor,
        String campo,
        int maximo,
        boolean obligatorio
    ) {
        String limpio = valor == null ? null : valor.trim();

        if (limpio == null || limpio.isEmpty()) {
            if (obligatorio) {
                throw new BadRequestException(
                    "El campo " + campo + " es obligatorio."
                );
            }

            return null;
        }

        if (limpio.length() > maximo) {
            throw new BadRequestException(
                "El campo " + campo +
                " admite hasta " + maximo + " caracteres."
            );
        }

        return limpio;
    }

    private void comprobarReferencia(
        String sql,
        String codigo,
        String mensaje
    ) {
        Number cantidad = (Number) entityManager
            .createNativeQuery(sql)
            .setParameter("codigo", codigo)
            .getSingleResult();

        if (cantidad.longValue() == 0) {
            throw new BadRequestException(mensaje);
        }
    }

    @Transactional
    public ProductoCreado registrar(ProductoRequest datos) {

        if (datos == null) {
            throw new BadRequestException(
                "Debes enviar los datos del producto."
            );
        }

        String nombre = texto(
            datos.nombre(), "nombre", 200, true
        );

        String clase = texto(
            datos.codClase(), "clase", 20, true
        );

        String marca = texto(
            datos.codMarca(), "marca", 20, false
        );

        String unidadMedida = texto(
            datos.codUm(), "unidad de medida", 10, true
        );

        String modelo = texto(
            datos.modelo(), "modelo", 100, false
        );

        String color = texto(
            datos.color(), "color", 80, false
        );

        comprobarReferencia(
            "SELECT COUNT(*) FROM clase_producto WHERE cod_clase = :codigo",
            clase,
            "La clase seleccionada no existe."
        );

        comprobarReferencia(
            "SELECT COUNT(*) FROM unidad_medida WHERE cod_um = :codigo",
            unidadMedida,
            "La unidad de medida seleccionada no existe."
        );

        if (marca != null) {
            comprobarReferencia(
                "SELECT COUNT(*) FROM marca WHERE cod_mar = :codigo",
                marca,
                "La marca seleccionada no existe."
            );
        }

        String codigo = "PRO-" + UUID.randomUUID()
            .toString()
            .replace("-", "")
            .substring(0, 20)
            .toUpperCase();

        entityManager.createNativeQuery("""
            INSERT INTO producto (
                cod_pro,
                cod_clase,
                cod_mar,
                cod_um,
                des_pro,
                modelo,
                color,
                estado
            )
            VALUES (
                :codigo,
                :clase,
                :marca,
                :unidadMedida,
                :nombre,
                :modelo,
                :color,
                'ACTIVO'
            )
            """)
            .setParameter("codigo", codigo)
            .setParameter("clase", clase)
            .setParameter("marca", marca)
            .setParameter("unidadMedida", unidadMedida)
            .setParameter("nombre", nombre)
            .setParameter("modelo", modelo)
            .setParameter("color", color)
            .executeUpdate();

        return new ProductoCreado(codigo, nombre);
    }
}