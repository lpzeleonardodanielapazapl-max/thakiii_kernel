package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import org.junit.jupiter.api.Test;

class PasswordServiceTest {
    @Test void lasClavesUsanSalYSePuedenVerificarSinGuardarTextoPlano() {
        var service = new PasswordService();
        String password = "ClaveDePrueba123!";
        String first = service.hash(password);
        String second = service.hash(password);
        assertNotEquals(first, second);
        assertFalse(first.contains(password));
        assertTrue(service.matches(password, first));
        assertFalse(service.matches("otra-clave", first));
        assertFalse(service.matches(password, "PENDIENTE_HASH_" + password));
        assertFalse(service.matches(password, null));
    }
}
