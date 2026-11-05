package com.thakivoyage.service;

import java.time.LocalDate;
import java.util.List;

import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.BadRequestException;
import jakarta.ws.rs.ClientErrorException;
import jakarta.ws.rs.NotFoundException;

@ApplicationScoped
public class UnidadInventarioService {
    @Inject
    EntityManager entityManager;

    public record Producto(String codigo, String nombre, String estado) {}
    public record Almacen(String codigo, String nombre) {}
    public record Unidad(long id, String codigoUnico, String almacen, String estadoFisico,
        Integer calificacion, boolean requiereArreglo, boolean requiereCambio,
        boolean requiereBaja, String fechaAlta, String fechaBaja, String observaciones, String codAlmacen) {}
    public record Ficha(Producto producto, List<Unidad> unidades, List<Almacen> almacenes) {}
    public record UnidadRequest(String codigoUnico, String codAlmacen, String estadoFisico,
        Integer calificacion, LocalDate fechaAlta, Boolean requiereArreglo,
        Boolean requiereCambio, Boolean requiereBaja, String observaciones) {}

    private Producto producto(String codigo) {
        List<?> filas = entityManager.createNativeQuery(
            "SELECT cod_pro, des_pro, estado FROM producto WHERE cod_pro = :codigo")
            .setParameter("codigo", codigo).getResultList();
        if (filas.isEmpty()) {
            throw new NotFoundException("Producto no encontrado.");
        }
        Object[] fila = (Object[]) filas.getFirst();
        return new Producto((String) fila[0], (String) fila[1], (String) fila[2]);
    }

    public Ficha consultar(String codigo) {
        Producto producto = producto(codigo);
        List<?> filas = entityManager.createNativeQuery("""
            SELECT u.id_unidad, u.codigo_unico, a.nombre, u.estado_fisico,
                   u.calificacion, u.requiere_arreglo, u.requiere_cambio, u.requiere_baja,
                   u.fecha_alta, u.fecha_baja, u.observaciones, u.cod_almacen
            FROM inventario_unidad u
            LEFT JOIN almacen a ON a.cod_almacen = u.cod_almacen
            WHERE u.cod_pro = :codigo
            ORDER BY u.id_unidad DESC
            """).setParameter("codigo", codigo).getResultList();
        List<Unidad> unidades = filas.stream().map(fila -> {
            Object[] v = (Object[]) fila;
            return new Unidad(((Number) v[0]).longValue(), (String) v[1], (String) v[2],
                (String) v[3], v[4] == null ? null : ((Number) v[4]).intValue(),
                marcado(v[5]), marcado(v[6]), marcado(v[7]),
                fecha(v[8]), fecha(v[9]), (String) v[10], (String) v[11]);
        }).toList();
        List<?> almacenes = entityManager.createNativeQuery(
            "SELECT cod_almacen, nombre FROM almacen WHERE estado = 'ACTIVO' ORDER BY nombre")
            .getResultList();
        return new Ficha(producto, unidades, almacenes.stream().map(fila -> {
            Object[] v = (Object[]) fila;
            return new Almacen((String) v[0], (String) v[1]);
        }).toList());
    }

    private static boolean marcado(Object valor) {
        return Boolean.TRUE.equals(valor) || (valor instanceof Number n && n.intValue() != 0);
    }

    public Unidad consultarUnidad(String codigo, long id) {
        return consultar(codigo).unidades().stream().filter(u -> u.id() == id).findFirst()
            .orElseThrow(() -> new NotFoundException("Unidad no encontrada para este producto."));
    }

    @Transactional
    public Unidad actualizar(String codigo, long id, UnidadRequest solicitud) {
        Unidad actual = consultarUnidad(codigo, id);
        UnidadRequest datos = validar(solicitud);
        if (actual.fechaBaja() != null) throw new BadRequestException("La unidad tiene una baja registrada y su historial se conserva sin cambios.");
        if (actual.codigoUnico() != null && !actual.codigoUnico().equals(datos.codigoUnico())) throw new BadRequestException("El código único ya asignado no puede cambiarse.");
        if (actual.codAlmacen() != null && !actual.codAlmacen().equals(datos.codAlmacen())) throw new BadRequestException("El almacén no se cambia mediante la edición de condición.");
        Number total = (Number) entityManager.createNativeQuery("SELECT COUNT(*) FROM almacen WHERE cod_almacen = :codigo AND estado = 'ACTIVO'")
            .setParameter("codigo", datos.codAlmacen()).getSingleResult();
        if (total.longValue() == 0) throw new BadRequestException("Selecciona un almacén activo existente.");
        entityManager.createNativeQuery("""
            UPDATE inventario_unidad SET codigo_unico = :unico, cod_almacen = :almacen,
                estado_fisico = :estado, calificacion = :calificacion, fecha_alta = :fecha,
                requiere_arreglo = :arreglo, requiere_cambio = :cambio, requiere_baja = :baja,
                observaciones = :observaciones
            WHERE id_unidad = :id AND cod_pro = :producto
            """).setParameter("unico", datos.codigoUnico()).setParameter("almacen", datos.codAlmacen())
            .setParameter("estado", datos.estadoFisico()).setParameter("calificacion", datos.calificacion())
            .setParameter("fecha", datos.fechaAlta()).setParameter("arreglo", datos.requiereArreglo())
            .setParameter("cambio", datos.requiereCambio()).setParameter("baja", datos.requiereBaja())
            .setParameter("observaciones", datos.observaciones()).setParameter("id", id).setParameter("producto", codigo)
            .executeUpdate();
        return consultarUnidad(codigo, id);
    }

    private static String fecha(Object valor) {
        return valor == null ? null : valor.toString();
    }

    private static String texto(String valor, String campo, int maximo, boolean obligatorio) {
        String limpio = valor == null ? "" : valor.strip();
        if (limpio.isEmpty() && obligatorio) {
            throw new BadRequestException("El campo " + campo + " es obligatorio.");
        }
        if (limpio.length() > maximo) {
            throw new BadRequestException("El campo " + campo + " admite hasta " + maximo + " caracteres.");
        }
        return limpio.isEmpty() ? null : limpio;
    }

    static UnidadRequest validar(UnidadRequest datos) {
        if (datos == null) {
            throw new BadRequestException("Debes enviar los datos de la unidad.");
        }
        if (datos.fechaAlta() == null || datos.fechaAlta().isAfter(LocalDate.now())) {
            throw new BadRequestException("La fecha de alta es obligatoria y no puede ser futura.");
        }
        if (datos.calificacion() != null && datos.calificacion() < 0) {
            throw new BadRequestException("La calificación no puede ser negativa.");
        }
        return new UnidadRequest(texto(datos.codigoUnico(), "código único", 50, true),
            texto(datos.codAlmacen(), "almacén", 20, true),
            texto(datos.estadoFisico(), "estado físico", 20, true), datos.calificacion(),
            datos.fechaAlta(), Boolean.TRUE.equals(datos.requiereArreglo()),
            Boolean.TRUE.equals(datos.requiereCambio()), Boolean.TRUE.equals(datos.requiereBaja()),
            texto(datos.observaciones(), "observaciones", 2000, false));
    }

    @Transactional
    public String registrar(String codigo, UnidadRequest solicitud) {
        UnidadRequest datos = validar(solicitud);
        if (!"ACTIVO".equals(producto(codigo).estado())) {
            throw new BadRequestException("Solo puedes registrar unidades de productos activos.");
        }
        Number almacenes = (Number) entityManager.createNativeQuery(
            "SELECT COUNT(*) FROM almacen WHERE cod_almacen = :codigo AND estado = 'ACTIVO'")
            .setParameter("codigo", datos.codAlmacen()).getSingleResult();
        if (almacenes.longValue() == 0) {
            throw new BadRequestException("Selecciona un almacén activo existente.");
        }
        Number duplicados = (Number) entityManager.createNativeQuery(
            "SELECT COUNT(*) FROM inventario_unidad WHERE codigo_unico = :codigo")
            .setParameter("codigo", datos.codigoUnico()).getSingleResult();
        if (duplicados.longValue() > 0) {
            throw new ClientErrorException("Ya existe una unidad con ese código único.", 409);
        }
        entityManager.createNativeQuery("""
            INSERT INTO inventario_unidad
                (cod_pro, cod_almacen, codigo_unico, estado_fisico, calificacion,
                 fecha_alta, requiere_arreglo, requiere_cambio, requiere_baja, observaciones)
            VALUES (:producto, :almacen, :codigo, :estado, :calificacion,
                    :fecha, :arreglo, :cambio, :baja, :observaciones)
            """)
            .setParameter("producto", codigo).setParameter("almacen", datos.codAlmacen())
            .setParameter("codigo", datos.codigoUnico()).setParameter("estado", datos.estadoFisico())
            .setParameter("calificacion", datos.calificacion()).setParameter("fecha", datos.fechaAlta())
            .setParameter("arreglo", datos.requiereArreglo()).setParameter("cambio", datos.requiereCambio())
            .setParameter("baja", datos.requiereBaja()).setParameter("observaciones", datos.observaciones())
            .executeUpdate();
        return datos.codigoUnico();
    }
}
