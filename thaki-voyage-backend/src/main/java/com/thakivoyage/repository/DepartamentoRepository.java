package com.thakivoyage.repository;

import com.thakivoyage.entity.Departamento;

import io.quarkus.hibernate.orm.panache.PanacheRepository;
import jakarta.enterprise.context.ApplicationScoped;

@ApplicationScoped
public class DepartamentoRepository implements PanacheRepository<Departamento> {
}