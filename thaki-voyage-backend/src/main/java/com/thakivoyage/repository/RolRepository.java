package com.thakivoyage.repository;

import com.thakivoyage.entity.Rol;

import io.quarkus.hibernate.orm.panache.PanacheRepository;
import jakarta.enterprise.context.ApplicationScoped;

@ApplicationScoped
public class RolRepository implements PanacheRepository<Rol>{
    
}
