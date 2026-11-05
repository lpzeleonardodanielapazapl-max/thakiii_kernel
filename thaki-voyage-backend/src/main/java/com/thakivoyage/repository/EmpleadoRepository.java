package com.thakivoyage.repository;

import com.thakivoyage.entity.Empleado;

import io.quarkus.hibernate.orm.panache.PanacheRepository;
import jakarta.enterprise.context.ApplicationScoped;

@ApplicationScoped
public class EmpleadoRepository implements PanacheRepository<Empleado> {
}