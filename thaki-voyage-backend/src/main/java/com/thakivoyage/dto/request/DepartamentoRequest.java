package com.thakivoyage.dto.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public class DepartamentoRequest {

    @NotBlank(message = "El nombre del departamento es obligatorio")
    @Size(max = 100)
    public String desDep;

    public String descripcion;
}