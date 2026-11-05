package com.thakivoyage.dto.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public class RolRequest {
       @NotBlank(message = "El nombre del rol es obligatorio")
    @Size(max = 50, message = "El nombre no puede superar los 50 caracteres")
    public String desRol;

    public String descripcion;
}
