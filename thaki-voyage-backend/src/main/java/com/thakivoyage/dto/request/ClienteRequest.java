package com.thakivoyage.dto.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public class ClienteRequest {

    @NotBlank(message = "codPersona es obligatorio")
    public String codPersona;

    @Size(max = 30)
    public String codigoCliente;

    public String estado = "ACTIVO";
}