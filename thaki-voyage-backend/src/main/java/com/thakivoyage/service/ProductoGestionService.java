package com.thakivoyage.service;

import java.math.BigDecimal;
import java.util.List;
import java.util.Set;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.BadRequestException;
import jakarta.ws.rs.NotFoundException;

@ApplicationScoped
public class ProductoGestionService {
    @Inject EntityManager em;

    public record Producto(String codigo, String nombre, String codClase, String codMarca,
        String codUm, String modelo, String color, BigDecimal stockMinimo, String estado) {}
    public record Datos(String nombre, String codClase, String codMarca, String codUm,
        String modelo, String color, BigDecimal stockMinimo, String estado) {}

    public Producto consultar(String codigo) {
        List<?> filas = em.createNativeQuery("""
            SELECT cod_pro, des_pro, cod_clase, cod_mar, cod_um, modelo, color, stock_minimo, estado
            FROM producto WHERE cod_pro = :codigo
            """).setParameter("codigo", codigo).getResultList();
        if (filas.isEmpty()) throw new NotFoundException("Producto no encontrado.");
        Object[] v = (Object[]) filas.getFirst();
        return new Producto((String) v[0], (String) v[1], (String) v[2], (String) v[3],
            (String) v[4], (String) v[5], (String) v[6], v[7] == null ? BigDecimal.ZERO : new BigDecimal(v[7].toString()), (String) v[8]);
    }

    private String texto(String value, String label, int max, boolean required) {
        String result = value == null || value.isBlank() ? null : value.strip();
        if (required && result == null) throw new BadRequestException(label + " es obligatorio.");
        if (result != null && result.length() > max) throw new BadRequestException(label + " supera " + max + " caracteres.");
        return result;
    }

    private void referencia(String tabla, String clave, String codigo) {
        Number total = (Number) em.createNativeQuery("SELECT COUNT(*) FROM " + tabla + " WHERE " + clave + " = :codigo")
            .setParameter("codigo", codigo).getSingleResult();
        if (total.longValue() == 0) throw new BadRequestException("Una de las opciones seleccionadas no existe.");
    }

    @Transactional
    public Producto actualizar(String codigo, Datos datos) {
        em.createNativeQuery("SELECT cod_pro FROM producto WHERE cod_pro=:codigo FOR UPDATE").setParameter("codigo",codigo).getResultList();
        Producto anterior = consultar(codigo);
        if (datos == null) throw new BadRequestException("Debes enviar los datos del producto.");
        String nombre = texto(datos.nombre(), "El nombre", 200, true);
        String clase = texto(datos.codClase(), "La clase", 20, true);
        String marca = texto(datos.codMarca(), "La marca", 20, false);
        String um = texto(datos.codUm(), "La unidad de medida", 10, true);
        String modelo = texto(datos.modelo(), "El modelo", 100, false);
        String color = texto(datos.color(), "El color", 80, false);
        if (!Set.of("ACTIVO", "INACTIVO").contains(datos.estado() == null ? "" : datos.estado())) throw new BadRequestException("Selecciona un estado válido.");
        BigDecimal minimo = datos.stockMinimo() == null ? BigDecimal.ZERO : datos.stockMinimo();
        if (minimo.signum() < 0 || minimo.scale() > 2 || minimo.compareTo(new BigDecimal("9999999999.99")) > 0) throw new BadRequestException("El stock mínimo debe ser positivo o cero, con hasta dos decimales.");
        referencia("clase_producto", "cod_clase", clase);
        referencia("unidad_medida", "cod_um", um);
        Number referencias = (Number) em.createNativeQuery("SELECT (SELECT COUNT(*) FROM inventario_unidad WHERE cod_pro=:codigo)+(SELECT COUNT(*) FROM inventario_stock WHERE cod_pro=:codigo)+(SELECT COUNT(*) FROM prestamo_detalle WHERE cod_pro=:codigo)").setParameter("codigo",codigo).getSingleResult();
        if (referencias.longValue() > 0) {
            if (!java.util.Objects.equals(um,anterior.codUm())) throw new BadRequestException("El producto ya tiene inventario o historial. Conserva su unidad de medida para no alterar cantidades anteriores.");
            if (!clase.equals(anterior.codClase())) {
                Object[] modoActual = (Object[]) em.createNativeQuery("SELECT es_serializado,es_consumible FROM clase_producto WHERE cod_clase=:codigo")
                    .setParameter("codigo", anterior.codClase()).getSingleResult();
                Object[] modoNuevo = (Object[]) em.createNativeQuery("SELECT es_serializado,es_consumible FROM clase_producto WHERE cod_clase=:codigo")
                    .setParameter("codigo", clase).getSingleResult();
                if (!java.util.Objects.equals(modoActual[0], modoNuevo[0]) || !java.util.Objects.equals(modoActual[1], modoNuevo[1])) {
                    throw new BadRequestException("La nueva clase usa otra forma de control. Conserva el modo individual, por cantidad o consumible de este producto.");
                }
            }
        }
        if (marca != null) referencia("marca", "cod_mar", marca);
        em.createNativeQuery("""
            UPDATE producto SET des_pro = :nombre, cod_clase = :clase, cod_mar = :marca,
                cod_um = :um, modelo = :modelo, color = :color, stock_minimo = :minimo, estado = :estado
            WHERE cod_pro = :codigo
            """).setParameter("nombre", nombre).setParameter("clase", clase).setParameter("marca", marca)
            .setParameter("um", um).setParameter("modelo", modelo).setParameter("color", color)
            .setParameter("minimo", minimo).setParameter("estado", datos.estado()).setParameter("codigo", codigo)
            .executeUpdate();
        return consultar(codigo);
    }
}
