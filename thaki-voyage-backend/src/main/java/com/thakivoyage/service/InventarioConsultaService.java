package com.thakivoyage.service;

import java.util.*;
import static com.thakivoyage.service.PortalQuery.valores;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.ws.rs.NotFoundException;

/** Consulta las tablas originales; las referencias del Excel no modifican disponibilidad. */
@ApplicationScoped
public class InventarioConsultaService {
    @Inject PortalQuery db;

    public Map<String,Object> resumen() {
        return valores("unidades",unidades(),"stock",stock(),"documentos",documentos(),"precios",precios(),"movimientos",movimientos(),"prestamos",prestamos());
    }
    public List<Map<String,Object>> unidades() {
        return db.lista("""
            SELECT u.id_unidad,p.cod_pro,p.des_pro,c.tipo_inv,u.codigo_unico,p.color,u.cod_almacen,
              COALESCE(a.nombre,'Ubicación pendiente'),u.estado_fisico,u.calificacion,u.requiere_arreglo,
              u.requiere_cambio,u.requiere_baja,u.fecha_baja,u.observaciones,p.estado,
              (SELECT COUNT(*) FROM prestamo_detalle d JOIN prestamo_concesion pr ON pr.cod_prestamo=d.cod_prestamo
               WHERE d.id_unidad=u.id_unidad AND COALESCE(d.devuelto,0)=0 AND pr.estado IN ('ACTIVO','PARCIAL'))
            FROM inventario_unidad u JOIN producto p ON p.cod_pro=u.cod_pro JOIN clase_producto c ON c.cod_clase=p.cod_clase
            LEFT JOIN almacen a ON a.cod_almacen=u.cod_almacen
            WHERE p.cod_pro NOT LIKE 'DEMO-%' AND p.des_pro NOT LIKE '[DEMO]%' ORDER BY p.des_pro,u.codigo_unico
            """,Map.of(),"id","producto","nombre","categoria","codigo","color","almacen","nombreAlmacen","estadoFisico","calificacion","requiereArreglo","requiereCambio","requiereBaja","fechaBaja","observaciones","estadoProducto","prestada");
    }
    public List<Map<String,Object>> stock() {
        return db.lista("""
            SELECT p.cod_pro,p.des_pro,c.tipo_inv,a.nombre,s.cantidad,s.costo_promedio,s.ubicacion_detalle,
              COALESCE(um.abreviatura,um.des_um,'Medida pendiente')
            FROM inventario_stock s JOIN producto p ON p.cod_pro=s.cod_pro JOIN clase_producto c ON c.cod_clase=p.cod_clase
            JOIN almacen a ON a.cod_almacen=s.cod_almacen LEFT JOIN unidad_medida um ON um.cod_um=p.cod_um
            WHERE p.cod_pro NOT LIKE 'DEMO-%' AND p.des_pro NOT LIKE '[DEMO]%' ORDER BY p.des_pro,a.nombre
            """,Map.of(),"producto","nombre","categoria","almacen","cantidad","costo","ubicacion","medida");
    }
    public List<Map<String,Object>> documentos() {
        return db.lista("SELECT cod_doc,tipo,numero,fecha,grupo,observaciones FROM documento_inventario ORDER BY fecha DESC,numero",Map.of(),"codigo","tipo","numero","fecha","grupo","observaciones");
    }
    public Map<String,Object> documento(String codigo) {
        var d=db.primero("SELECT cod_doc,tipo,numero,fecha,proveedor,departamento,destino_a,grupo,recibido_por,observaciones,total FROM documento_inventario WHERE cod_doc=:codigo",Map.of("codigo",codigo),"codigo","tipo","numero","fecha","proveedor","departamento","destino","grupo","recibidoPor","observaciones","total");
        if(d.isEmpty())throw new NotFoundException("Documento no encontrado.");
        d.put("items",db.lista("SELECT descripcion,cantidad,nota_observacion,estado_articulo,devuelto FROM documento_detalle WHERE cod_doc=:codigo ORDER BY id_detalle",Map.of("codigo",codigo),"descripcion","cantidad","observaciones","calificacion","devuelto"));
        String movimiento = codigo.contains("-") ? "MOV-"+codigo.substring(codigo.indexOf('-')+1) : codigo;
        d.put("movimientos",db.lista("""
            SELECT tipo_movimiento,fecha,cod_almacen_origen,cod_almacen_destino,observaciones
            FROM movimiento_inventario
            WHERE documento_ref=:codigo OR cod_movimiento=:movimiento
            ORDER BY fecha
            """,Map.of("codigo",codigo,"movimiento",movimiento),"tipo","fecha","origen","destino","observaciones"));
        return d;
    }
    public List<Map<String,Object>> precios() {
        return db.lista("SELECT p.des_pro,pr.fec_com,pr.mon_pre,pr.monto_pre FROM precio pr JOIN producto p ON p.cod_pro=pr.cod_pro WHERE p.cod_pro NOT LIKE 'DEMO-%' AND p.des_pro NOT LIKE '[DEMO]%' ORDER BY pr.fec_com DESC,p.des_pro",Map.of(),"nombre","fecha","moneda","monto");
    }
    public List<Map<String,Object>> movimientos() {
        return db.lista("SELECT m.tipo_movimiento,m.fecha,m.documento_ref,a.nombre,b.nombre,m.observaciones FROM movimiento_inventario m LEFT JOIN almacen a ON a.cod_almacen=m.cod_almacen_origen LEFT JOIN almacen b ON b.cod_almacen=m.cod_almacen_destino ORDER BY m.fecha DESC LIMIT 500",Map.of(),"tipo","fecha","documento","origen","destino","observaciones");
    }
    public List<Map<String,Object>> prestamos() {
        return db.lista("""
            SELECT pr.cod_prestamo,pr.tipo,CONCAT(pe.nombres,' ',pe.apellidos),pr.fecha_prestamo,pr.estado,
              p.des_pro,u.codigo_unico,d.cantidad,d.devuelto,d.observaciones
            FROM prestamo_concesion pr JOIN prestamo_detalle d ON d.cod_prestamo=pr.cod_prestamo
            JOIN producto p ON p.cod_pro=d.cod_pro LEFT JOIN persona pe ON pe.cod_persona=pr.cod_persona
            LEFT JOIN inventario_unidad u ON u.id_unidad=d.id_unidad ORDER BY pr.fecha_prestamo DESC,pr.cod_prestamo
            """,Map.of(),"codigo","tipo","responsable","fecha","estado","nombre","unidad","cantidad","devuelto","observaciones");
    }
}
