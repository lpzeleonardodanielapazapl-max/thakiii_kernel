<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LimitesAccesoTest extends TestCase
{
    public function test_agotar_ingresos_no_bloquea_el_registro(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*/api/acceso/ingresar' => Http::response(['mensaje' => 'Credenciales incorrectas.'], 401)]);

        for ($intento = 0; $intento < 8; $intento++) {
            $this->post('/ingresar', ['email' => 'limite@example.test', 'password' => 'incorrecta'])
                ->assertRedirect()->assertSessionHasErrors('acceso');
        }
        $this->post('/ingresar', ['email' => 'limite@example.test', 'password' => 'incorrecta'])->assertStatus(429);

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post('/registro', [])->assertRedirect()->assertSessionHasErrors('email');
        }
        $this->post('/registro', [])->assertStatus(429);
        Http::assertSentCount(8);
    }

    public function test_elecciones_de_oferta_no_consumen_intentos_de_ingreso(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*/api/acceso/ingresar' => Http::response(['mensaje' => 'Credenciales incorrectas.'], 401)]);

        for ($intento = 0; $intento < 20; $intento++) {
            $this->post('/alojamientos/elegir', [])->assertRedirect()->assertSessionHasErrors('hotel');
        }
        $this->post('/alojamientos/elegir', [])->assertStatus(429);
        $this->post('/ingresar', ['email' => 'limite@example.test', 'password' => 'incorrecta'])
            ->assertRedirect()->assertSessionHasErrors('acceso');
        Http::assertSentCount(1);
    }
}
