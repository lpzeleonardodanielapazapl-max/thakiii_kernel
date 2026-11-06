package com.thakivoyage.service;

import java.util.*;
import com.thakivoyage.entity.Usuario;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.persistence.EntityManager;

@ApplicationScoped
public class PortalQuery {
    @Inject EntityManager em;

    public List<Usuario> usuariosPorCorreo(String email) {
        return em.createQuery("FROM Usuario WHERE lower(email) = :email", Usuario.class)
            .setParameter("email", email).getResultList();
    }

    public void persistirUsuario(Usuario usuario) {
        em.persist(usuario);
        em.flush();
    }

    public List<Map<String, Object>> lista(String sql, Map<String, ?> parametros, String... columnas) {
        var query = em.createNativeQuery(sql);
        parametros.forEach(query::setParameter);
        List<?> filas = query.getResultList();
        return filas.stream().map(fila -> {
            Object[] valores = fila instanceof Object[] a ? a : new Object[] {fila};
            Map<String, Object> result = new LinkedHashMap<>();
            for (int i = 0; i < columnas.length; i++) {
                Object valor = valores[i];
                if (valor instanceof java.sql.Timestamp t) valor = t.toLocalDateTime().toString();
                else if (valor instanceof java.sql.Date d) valor = d.toLocalDate().toString();
                result.put(columnas[i], valor);
            }
            return result;
        }).toList();
    }

    public Map<String, Object> primero(String sql, Map<String, ?> parametros, String... columnas) {
        var rows = lista(sql, parametros, columnas);
        return rows.isEmpty() ? Map.of() : rows.getFirst();
    }

    public int ejecutar(String sql, Map<String, ?> parametros) {
        var query = em.createNativeQuery(sql);
        parametros.forEach(query::setParameter);
        return query.executeUpdate();
    }

    public static Map<String, Object> valores(Object... entries) {
        Map<String, Object> result = new LinkedHashMap<>();
        for (int i = 0; i < entries.length; i += 2) result.put((String) entries[i], entries[i + 1]);
        return result;
    }
}
