package com.thakivoyage.entity;

import io.quarkus.hibernate.orm.panache.PanacheEntityBase;
import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "cargo")
public class Cargo extends PanacheEntityBase {

    @Id
    @Column(name = "cod_cargo", length = 20)
    public String codCargo;

    @Column(name = "des_cargo", length = 100, nullable = false, unique = true)
    public String desCargo;

    @Column(name = "descripcion", columnDefinition = "TEXT")
    public String descripcion;
}