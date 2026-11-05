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

    @Size(min = 8, max = 255, message = "La contraseña debe tener al menos 8 caracteres")
    public String password;

    @NotBlank(message = "codRol es obligatorio")
    public String codRol;

    public Boolean activo = true;
}
