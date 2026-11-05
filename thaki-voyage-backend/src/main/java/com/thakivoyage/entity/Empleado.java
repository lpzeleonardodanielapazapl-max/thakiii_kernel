package com.thakivoyage.entity;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;

import io.quarkus.hibernate.orm.panache.PanacheEntityBase;
import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "empleado")
public class Empleado extends PanacheEntityBase {

    @Id
    @Column(name = "cod_empleado", length = 20)
    public String codEmpleado;

    @Column(name = "cod_persona", length = 20, nullable = false, unique = true)
    public String codPersona;

    @Column(name = "cod_cargo", length = 20)
    public String codCargo;

    @Column(name = "cod_dep", length = 20)
    public String codDep;

    @Column(name = "cod_sucursal", length = 20)
    public String codSucursal;

    @Column(name = "fecha_ingreso")
    public LocalDate fechaIngreso;

    @Column(name = "fecha_retiro")
    public LocalDate fechaRetiro;

    @Column(name = "salario", precision = 12, scale = 2)
    public BigDecimal salario;

    @Column(name = "tipo_contrato", length = 50)
    public String tipoContrato;

    @Column(name = "es_guia")
    public Boolean esGuia;

    @Column(name = "es_asesor")
    public Boolean esAsesor;

    @Column(name = "es_conductor")
    public Boolean esConductor;

    @Column(name = "estado", length = 20)
    public String estado;

    @Column(name = "created_at")
    public LocalDateTime createdAt;

    @Column(name = "updated_at")
    public LocalDateTime updatedAt;
}