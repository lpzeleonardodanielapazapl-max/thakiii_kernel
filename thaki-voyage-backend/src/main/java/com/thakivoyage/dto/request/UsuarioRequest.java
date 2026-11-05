package com.thakivoyage.dto.request;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public class UsuarioRequest {

    @Size(max = 60)
    public String username;

    @NotBlank(message = "El email es obligatorio")
    @Email(message = "Email invalido")
    @Size(max = 150)
    public String email;

    @NotBlank(message = "La contrasena es obligatoria")
    @Size(min = 6, max = 255, message = "La contrasena debe tener al menos 6 caracteres")
    public String password;

    @NotBlank(message = "codRol es obligatorio")
    public String codRol;

    public String codEmpleado;

    public String codCliente;

    public Boolean activo = true;
}