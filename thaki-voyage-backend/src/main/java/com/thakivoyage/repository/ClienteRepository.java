package com.thakivoyage.repository;

import com.thakivoyage.entity.Cliente;

import io.quarkus.hibernate.orm.panache.PanacheRepository;
import jakarta.enterprise.context.ApplicationScoped;

@ApplicationScoped
public class ClienteRepository implements PanacheRepository<Cliente> {
}