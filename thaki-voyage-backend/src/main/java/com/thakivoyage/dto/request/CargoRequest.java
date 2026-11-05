package com.thakivoyage.dto.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public class CargoRequest {

    @NotBlank(message = "El nombre del cargo es obligatorio")
    @Size(max = 100)
    public String desCargo;

    public String descripcion;
}