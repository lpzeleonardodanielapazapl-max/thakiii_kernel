package com.thakivoyage.dto.request;

import java.time.LocalDate;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public class PersonaRequest {

    @Size(max = 20)
    public String tipoDocumento;

    @Size(max = 30)
    public String numeroDocumento;

    @NotBlank(message = "Los nombres son obligatorios")
    @Size(max = 100)
    public String nombres;

    @NotBlank(message = "Los apellidos son obligatorios")
    @Size(max = 100)
    public String apellidos;

    @Size(max = 30)
    public String telefono;

    @Email(message = "Email inválido")
    @Size(max = 150)
    public String email;

    @Size(max = 200)
    public String direccion;

    public LocalDate fechaNacimiento;

    @Size(max = 20)
    public String genero;
}
