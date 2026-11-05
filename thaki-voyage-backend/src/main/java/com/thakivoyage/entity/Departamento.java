package com.thakivoyage.entity;

import io.quarkus.hibernate.orm.panache.PanacheEntityBase;
import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "departamento")
public class Departamento extends PanacheEntityBase {

    @Id
    @Column(name = "cod_dep", length = 20)
    public String codDep;

    @Column(name = "des_dep", length = 100, nullable = false, unique = true)
    public String desDep;

    @Column(name = "descripcion", columnDefinition = "TEXT")
    public String descripcion;
}