package com.thakivoyage.service;

import java.math.BigDecimal;
import java.util.*;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.ws.rs.BadRequestException;

@ApplicationScoped
public class OfertaService {
    @Inject PortalQuery db;

    public List<Map<String, Object>> hoteles() {
        var hoteles = db.lista("""
            SELECT h.cod_hotel, h.nombre, COALESCE(h.descripcion,''), COALESCE(h.categoria,0),
                   COALESCE(h.ciudad,''), COALESCE(h.pais,'Bolivia'), COALESCE(h.servicios,''),
                   COALESCE(h.direccion,''), COALESCE(TIME_FORMAT(h.check_in,'%H:%i'),''),
                   COALESCE(TIME_FORMAT(h.check_out,'%H:%i'),''), COALESCE(h.imagen_url,'')
            FROM hotel h WHERE h.estado='ACTIVO' ORDER BY h.ciudad, h.nombre
            """, Map.of(), "codigo", "nombre", "descripcion", "categoria", "ciudad", "pais", "servicios", "direccion", "checkIn", "checkOut", "imagen");
        for (var hotel : hoteles) {
            hotel.put("habitaciones", db.lista("""
                SELECT t.cod_tipo_hab, t.nombre, COALESCE(t.descripcion,''), COALESCE(t.capacidad,1),
                       COALESCE(t.num_camas,1), COALESCE(t.tipo_camas,''),
                       tar.precio_noche, tar.moneda, tar.incluye_desayuno
                FROM hotel_tipo_habitacion t
                JOIN hotel_tarifa tar ON tar.cod_tarifa = (
                    SELECT tar2.cod_tarifa FROM hotel_tarifa tar2
                    WHERE tar2.cod_tipo_hab = t.cod_tipo_hab
                      AND tar2.fecha_inicio <= CURRENT_DATE
                      AND (tar2.fecha_fin IS NULL OR tar2.fecha_fin >= CURRENT_DATE)
                    ORDER BY tar2.fecha_inicio DESC LIMIT 1)
                WHERE t.cod_hotel = :hotel AND t.estado = 'ACTIVO'
                ORDER BY tar.precio_noche, t.nombre
                """, Map.of("hotel", hotel.get("codigo")),
                "codigo", "nombre", "descripcion", "capacidad", "camas", "tipoCamas", "precioNoche", "moneda", "desayuno"));
        }
        return hoteles;
    }

    public Map<String, Object> transporte() {
        return PortalQuery.valores(
            "servicios", db.lista("""
                SELECT cod_servicio, nombre, COALESCE(descripcion,''), COALESCE(tipo,''), COALESCE(origen,''),
                       COALESCE(destino,''), distancia_km, duracion_min, precio_base, COALESCE(moneda,'BOB'), capacidad_max
                FROM servicio_transporte WHERE estado='ACTIVO' ORDER BY origen, nombre
                """, Map.of(), "codigo", "nombre", "descripcion", "tipo", "origen", "destino", "distanciaKm", "duracionMin", "precio", "moneda", "capacidad"),
            "vehiculos", db.lista("""
                SELECT cod_vehiculo, COALESCE(tipo,''), COALESCE(marca,''), COALESCE(modelo,''), COALESCE(capacidad,0)
                FROM vehiculo WHERE estado='DISPONIBLE' ORDER BY capacidad, tipo, marca
                """, Map.of(), "codigo", "tipo", "marca", "modelo", "capacidad"));
    }

    public String etiquetaHotel(String hotel, String habitacion) {
        if (blanco(hotel) || blanco(habitacion)) return null;
        var fila = db.primero("""
            SELECT h.nombre, COALESCE(h.ciudad,''), t.nombre, tar.precio_noche, tar.moneda, tar.incluye_desayuno
            FROM hotel h
            JOIN hotel_tipo_habitacion t ON t.cod_hotel = h.cod_hotel
            JOIN hotel_tarifa tar ON tar.cod_tarifa = (
                SELECT tar2.cod_tarifa FROM hotel_tarifa tar2
                WHERE tar2.cod_tipo_hab = t.cod_tipo_hab
                  AND tar2.fecha_inicio <= CURRENT_DATE
                  AND (tar2.fecha_fin IS NULL OR tar2.fecha_fin >= CURRENT_DATE)
                ORDER BY tar2.fecha_inicio DESC LIMIT 1)
            WHERE h.cod_hotel = :hotel AND t.cod_tipo_hab = :habitacion AND h.estado = 'ACTIVO' AND t.estado = 'ACTIVO'
            """, Map.of("hotel", hotel.strip(), "habitacion", habitacion.strip()),
            "hotel", "ciudad", "habitacion", "precio", "moneda", "desayuno");
        if (fila.isEmpty()) throw new BadRequestException("El alojamiento elegido ya no está disponible. Elige otra opción.");
        String ciudad = fila.get("ciudad").toString();
        String texto = fila.get("hotel") + (ciudad.isBlank() ? "" : ", " + ciudad) + " · " + fila.get("habitacion")
            + " · " + dinero(fila.get("precio"), fila.get("moneda")) + "/noche"
            + (entero(fila.get("desayuno")) == 1 ? " · desayuno" : "");
        return recortar(texto);
    }

    public String etiquetaTransporte(String servicio) {
        if (blanco(servicio)) return null;
        var fila = db.primero("""
            SELECT nombre, COALESCE(origen,''), COALESCE(destino,''), precio_base, COALESCE(moneda,'BOB'), COALESCE(tipo,'')
            FROM servicio_transporte WHERE cod_servicio = :codigo AND estado = 'ACTIVO'
            """, Map.of("codigo", servicio.strip()), "nombre", "origen", "destino", "precio", "moneda", "tipo");
        if (fila.isEmpty()) throw new BadRequestException("El traslado elegido ya no está disponible. Elige otra opción.");
        String ruta = fila.get("origen").toString().isBlank() ? "" : " · " + fila.get("origen") + " → " + fila.get("destino");
        String tipo = fila.get("tipo").toString().isBlank() ? "" : " · " + fila.get("tipo");
        return recortar(fila.get("nombre") + ruta + tipo + " · " + dinero(fila.get("precio"), fila.get("moneda")));
    }

    private static boolean blanco(String value) {
        return value == null || value.isBlank();
    }

    private static String recortar(String texto) {
        return texto.length() <= 200 ? texto : texto.substring(0, 200);
    }

    static String dinero(Object precio, Object moneda) {
        String monto = precio instanceof BigDecimal numero ? numero.stripTrailingZeros().toPlainString() : String.valueOf(precio);
        return monto + " " + moneda;
    }

    private static int entero(Object value) {
        return value instanceof Number numero ? numero.intValue() : 0;
    }
}
