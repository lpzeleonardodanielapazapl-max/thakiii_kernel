package com.thakivoyage.dto.request;

import java.math.BigDecimal;
import java.time.LocalDate;

import jakarta.validation.constraints.NotBlank;

public class EmpleadoRequest {

    @NotBlank(message = "codPersona es obligatorio")
    public String codPersona;

    public String codCargo;

    public String codDep;

    public String codSucursal;

    public LocalDate fechaIngreso;

    public LocalDate fechaRetiro;

    public BigDecimal salario;

    public String tipoContrato;

    public Boolean esGuia = false;

    public Boolean esAsesor = false;

    public Boolean esConductor = false;

    public String estado = "ACTIVO";
}