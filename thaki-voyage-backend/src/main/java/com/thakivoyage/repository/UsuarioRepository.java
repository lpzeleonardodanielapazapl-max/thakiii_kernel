package com.thakivoyage.repository;

import com.thakivoyage.entity.Usuario;
import io.quarkus.hibernate.orm.panache.PanacheRepository;
import jakarta.enterprise.context.ApplicationScoped;

@ApplicationScoped
public class UsuarioRepository implements PanacheRepository<Usuario> {

    public Usuario findByEmail(String email) {
        return find("email", email).firstResult();
    }

    public Usuario findByUsername(String username) {
        return find("username", username).firstResult();
    }
}