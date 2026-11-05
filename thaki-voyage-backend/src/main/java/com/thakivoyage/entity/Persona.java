package com.thakivoyage.entity;


import java.time.LocalDate;
import java.time.LocalDateTime;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "persona")
public class Persona {

    @Id
    @Column(name = "cod_persona", length = 20)
    public String codPersona;

    @Column(name = "tipo_documento", length = 20)
    public String tipoDocumento;

    @Column(name = "numero_documento", length = 30, unique = true)
    public String numeroDocumento;

    @Column(name = "nombres", length = 100, nullable = false)
    public String nombres;

    @Column(name = "apellidos", length = 100, nullable = false)
    public String apellidos;

    @Column(name = "telefono", length = 30)
    public String telefono;

    @Column(name = "email", length = 150)
    public String email;

    @Column(name = "direccion", length = 200)
    public String direccion;

    @Column(name = "fecha_nacimiento")
    public LocalDate fechaNacimiento;

    @Column(name = "genero", length = 20)
    public String genero;

    @Column(name = "created_at")
    public LocalDateTime createdAt;

    @Column(name = "updated_at")
    public LocalDateTime updatedAt; 
}
