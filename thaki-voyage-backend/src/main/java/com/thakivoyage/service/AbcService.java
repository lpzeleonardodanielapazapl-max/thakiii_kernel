package com.thakivoyage.service;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;

import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;

@ApplicationScoped
public class AbcService {

    @Inject
    EntityManager entityManager;

    private record ProductoBase(
        String codigo,
        String nombre,
        long unidades,
        BigDecimal precio,
        String fechaPrecio
    ) {}

    public record ProductoAbc(
        String codigo,
        String nombre,
        long unidades,
        BigDecimal precioUnitario,
        String fechaPrecio,
        BigDecimal valorTotal,
        BigDecimal porcentaje,
        BigDecimal porcentajeAcumulado,
        String clasificacion
    ) {}

    public record ResultadoAbc(
        String moneda,
        BigDecimal valorTotal,
        List<ProductoAbc> productos,
        List<String> sinPrecio,
        List<String> sinUnidades
    ) {}

    public ResultadoAbc calcular() {

        String sql = """
            SELECT
                p.cod_pro,
                p.des_pro,
                COALESCE(u.cantidad, 0),
                pr.monto_pre,
                pr.fec_com

            FROM producto p

            LEFT JOIN (
                SELECT cod_pro, COUNT(*) AS cantidad
                FROM inventario_unidad
                WHERE fecha_baja IS NULL
                GROUP BY cod_pro
            ) u ON u.cod_pro = p.cod_pro

            LEFT JOIN (
                SELECT
                    cod_pro,
                    monto_pre,
                    fec_com,
                    ROW_NUMBER() OVER (
                        PARTITION BY cod_pro
                        ORDER BY fec_com DESC, cod_pre DESC
                    ) AS posicion
                FROM precio
                WHERE mon_pre = 'BOB'
                  AND fec_com <= CURRENT_DATE
            ) pr ON pr.cod_pro = p.cod_pro
                AND pr.posicion = 1

            WHERE p.estado = 'ACTIVO'
            ORDER BY p.cod_pro
            """;

        List<?> filas = entityManager
            .createNativeQuery(sql)
            .getResultList();

        List<ProductoBase> productos = new ArrayList<>();
        List<String> sinPrecio = new ArrayList<>();
        List<String> sinUnidades = new ArrayList<>();

        for (Object fila : filas) {
            Object[] valores = (Object[]) fila;

            String codigo = (String) valores[0];
            String nombre = (String) valores[1];
            long unidades = ((Number) valores[2]).longValue();

            if (unidades == 0) {
                sinUnidades.add(codigo + " · " + nombre);
                continue;
            }

            if (valores[3] == null) {
                sinPrecio.add(codigo + " · " + nombre);
                continue;
            }

            BigDecimal precio = new BigDecimal(valores[3].toString());

            if (precio.signum() <= 0) {
                sinPrecio.add(codigo + " · " + nombre);
                continue;
            }

            productos.add(new ProductoBase(
                codigo,
                nombre,
                unidades,
                precio,
                valores[4].toString()
            ));
        }

        productos.sort(
            Comparator
                .comparing(
                    (ProductoBase p) ->
                        p.precio().multiply(
                            BigDecimal.valueOf(p.unidades())
                        )
                )
                .reversed()
                .thenComparing(ProductoBase::codigo)
        );

        BigDecimal total = BigDecimal.ZERO;

        for (ProductoBase p : productos) {
            total = total.add(
                p.precio().multiply(BigDecimal.valueOf(p.unidades()))
            );
        }

        List<ProductoAbc> resultado = new ArrayList<>();
        BigDecimal acumulado = BigDecimal.ZERO;

        BigDecimal limiteA = total.multiply(new BigDecimal("0.80"));
        BigDecimal limiteB = total.multiply(new BigDecimal("0.95"));

        for (ProductoBase p : productos) {

            // El artículo que cruza un límite permanece en ese grupo.
            String categoria = acumulado.compareTo(limiteA) < 0
                ? "A"
                : acumulado.compareTo(limiteB) < 0 ? "B" : "C";

            BigDecimal valor = p.precio().multiply(
                BigDecimal.valueOf(p.unidades())
            );

            acumulado = acumulado.add(valor);

            BigDecimal porcentaje = valor
                .multiply(new BigDecimal("100"))
                .divide(total, 4, RoundingMode.HALF_UP);

            BigDecimal porcentajeAcumulado = acumulado
                .multiply(new BigDecimal("100"))
                .divide(total, 4, RoundingMode.HALF_UP);

            resultado.add(new ProductoAbc(
                p.codigo(),
                p.nombre(),
                p.unidades(),
                p.precio(),
                p.fechaPrecio(),
                valor,
                porcentaje,
                porcentajeAcumulado,
                categoria
            ));
        }

        return new ResultadoAbc(
            "BOB",
            total,
            resultado,
            sinPrecio,
            sinUnidades
        );
    }
}