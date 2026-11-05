package com.thakivoyage.entity;

import java.time.LocalDateTime;

import io.quarkus.hibernate.orm.panache.PanacheEntityBase;
import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "cliente")
public class Cliente extends PanacheEntityBase {

    @Id
    @Column(name = "cod_cliente", length = 20)
    public String codCliente;

    @Column(name = "cod_persona", length = 20)
    public String codPersona;

    @Column(name = "codigo_cliente", length = 30, unique = true)
    public String codigoCliente;

    @Column(name = "estado", length = 20)
    public String estado;

    @Column(name = "created_at")
    public LocalDateTime createdAt;
}