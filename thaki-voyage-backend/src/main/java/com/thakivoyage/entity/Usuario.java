package com.thakivoyage.entity;

import java.time.LocalDateTime;
import com.fasterxml.jackson.annotation.JsonIgnore;

import io.quarkus.hibernate.orm.panache.PanacheEntityBase;
import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "usuario")
public class Usuario extends PanacheEntityBase {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @Column(name = "id_usuario")
    @org.hibernate.annotations.JdbcTypeCode(org.hibernate.type.SqlTypes.INTEGER)
    public Long idUsuario;

    @Column(name = "username", length = 60, unique = true)
    public String username;

    @Column(name = "email", length = 150, unique = true)
    public String email;

    @Column(name = "password_hash", length = 255)
    @JsonIgnore
    public String passwordHash;

    @Column(name = "cod_rol", length = 20)
    public String codRol;

    @Column(name = "activo")
    public Boolean activo;

    @Column(name = "email_verificado")
    public Boolean emailVerificado;

    @Column(name = "ultimo_acceso")
    public LocalDateTime ultimoAcceso;

    @Column(name = "created_at")
    public LocalDateTime createdAt;
}
