package com.thakivoyage.service;

import java.util.List;

import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;

@ApplicationScoped
public class InventarioService {

    @Inject
    EntityManager entityManager;

    public record ProductoResumen(
        String codigo,
        String nombre,
        String marca,
        String clase,
        String categoria,
        long unidadesRegistradas,
        long unidadesSinBaja,
        long unidadesRequierenArreglo,
        long unidadesConBaja
    ) {}

    public List<ProductoResumen> listarProductos() {

        String sql = """
            SELECT
                p.cod_pro,
                p.des_pro,
                COALESCE(m.des_mar, 'Sin marca'),
                c.des_clase,
                COALESCE(c.tipo_inv, 'SIN CATEGORIA'),
                COUNT(u.id_unidad),
                COALESCE(SUM(
                    CASE
                        WHEN u.id_unidad IS NOT NULL
                         AND u.fecha_baja IS NULL
                        THEN 1 ELSE 0
                    END
                ), 0),
                COALESCE(SUM(
                    CASE
                        WHEN u.id_unidad IS NOT NULL
                         AND u.fecha_baja IS NULL
                         AND u.requiere_arreglo = 1
                        THEN 1 ELSE 0
                    END
                ), 0),
                COALESCE(SUM(
                    CASE
                        WHEN u.id_unidad IS NOT NULL
                         AND u.fecha_baja IS NOT NULL
                        THEN 1 ELSE 0
                    END
                ), 0)
            FROM producto p
            JOIN clase_producto c ON c.cod_clase = p.cod_clase
            LEFT JOIN marca m ON m.cod_mar = p.cod_mar
            LEFT JOIN inventario_unidad u ON u.cod_pro = p.cod_pro
            WHERE p.estado = 'ACTIVO'
            GROUP BY
                p.cod_pro,
                p.des_pro,
                m.des_mar,
                c.des_clase,
                c.tipo_inv
            ORDER BY p.des_pro
            """;

        List<?> filas = entityManager
            .createNativeQuery(sql)
            .getResultList();

        return filas.stream()
            .map(fila -> {
                Object[] valores = (Object[]) fila;

                return new ProductoResumen(
                    (String) valores[0],
                    (String) valores[1],
                    (String) valores[2],
                    (String) valores[3],
                    (String) valores[4],
                    ((Number) valores[5]).longValue(),
                    ((Number) valores[6]).longValue(),
                    ((Number) valores[7]).longValue(),
                    ((Number) valores[8]).longValue()
                );
            })
            .toList();
    }
}