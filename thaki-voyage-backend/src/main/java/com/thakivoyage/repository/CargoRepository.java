package com.thakivoyage.repository;

import com.thakivoyage.entity.Cargo;

import io.quarkus.hibernate.orm.panache.PanacheRepository;
import jakarta.enterprise.context.ApplicationScoped;

@ApplicationScoped
public class CargoRepository implements PanacheRepository<Cargo> {
}