package com.thakivoyage.entity;

import java.time.LocalDateTime;

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
    public Long idUsuario;

    @Column(name = "username", length = 60, unique = true)
    public String username;

    @Column(name = "email", length = 150, unique = true)
    public String email;

    @Column(name = "password_hash", length = 255)
    public String passwordHash;

    @Column(name = "cod_rol", length = 20)
    public String codRol;

    @Column(name = "cod_empleado", length = 20)
    public String codEmpleado;

    @Column(name = "cod_cliente", length = 20)
    public String codCliente;

    @Column(name = "activo")
    public Boolean activo;

    @Column(name = "email_verificado")
    public Boolean emailVerificado;

    @Column(name = "ultimo_acceso")
    public LocalDateTime ultimoAcceso;

    @Column(name = "created_at")
    public LocalDateTime createdAt;
}