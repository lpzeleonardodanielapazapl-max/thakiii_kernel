package com.thakivoyage.entity;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "rol")
public class Rol {
    
    @Id
    @Column(name = "cod_rol", length = 20)
    public String codRol;

    @Column(name = "des_rol", length = 50, nullable = false, unique = true)
    public String desRol;

    @Column(name = "descripcion", columnDefinition = "TEXT")
    public String descripcion;
    
}
