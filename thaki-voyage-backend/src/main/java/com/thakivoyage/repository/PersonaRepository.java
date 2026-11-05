package com.thakivoyage.repository;

import com.thakivoyage.entity.Persona;

import io.quarkus.hibernate.orm.panache.PanacheRepository;
import jakarta.enterprise.context.ApplicationScoped;

@ApplicationScoped
public class PersonaRepository implements PanacheRepository<Persona>{
    
}
