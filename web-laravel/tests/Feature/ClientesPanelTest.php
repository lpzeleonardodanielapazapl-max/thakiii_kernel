<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientesPanelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['*/api/acceso/sesion' => fn () => Http::response(session('usuario'))]);
        $this->withSession(['api_token' => 'staff', 'usuario' => ['id' => 1, 'rol' => 'ADMIN', 'nombre' => 'Admin']]);
    }

    private function cliente(): array
    {
        return ['codigo' => 'CLI-1', 'nombre' => 'Ana Pérez', 'email' => 'ana@example.test', 'telefono' => '123456', 'estado' => 'ACTIVO', 'registro' => '2026-11-17 09:00:00', 'revisado' => null, 'solicitudesNuevas' => 1, 'mensajesNuevos' => 2];
    }

    public function test_lista_muestra_avisos_y_filtra_clientes(): void
    {
        Http::fake(['*/api/atencion/clientes' => Http::response([$this->cliente()])]);
        $this->get('/clientes?ver=nuevos')->assertOk()->assertSeeText('Nuevo')->assertSeeText('2 mensajes sin leer')->assertSee(route('clientes.show', 'CLI-1'));
        $this->get('/clientes?q=otra')->assertOk()->assertDontSeeText('Ana Pérez');
        $this->get('/clientes?ver=pendientes')->assertOk()->assertSeeText('Ana Pérez');
        $this->get('/clientes?ver=invalido')->assertSessionHasErrors('ver');
    }

    public function test_ficha_reune_solicitudes_chat_y_reservas(): void
    {
        Http::fake(['*/api/atencion/clientes/CLI-1' => Http::response([...$this->cliente(), 'solicitudes' => [], 'conversaciones' => [], 'reservas' => []])]);
        $this->get('/clientes/CLI-1')->assertOk()->assertSeeText('Ana Pérez')->assertSeeText('Solicitudes y propuestas')->assertSeeText('Conversaciones')->assertSeeText('Reservas y pagos');
        Http::assertSentCount(2);
    }

    public function test_revisar_y_leer_son_operaciones_explicitas(): void
    {
        Http::fake(['*/api/atencion/clientes/CLI-1/revisar' => Http::response(null, 204), '*/api/atencion/conversaciones/7/leer' => Http::response(null, 204)]);
        $this->from('/clientes/CLI-1')->post('/clientes/CLI-1/revisar')->assertRedirect('/clientes/CLI-1');
        $this->from('/atencion/conversaciones/7')->post('/atencion/conversaciones/7/leer')->assertRedirect('/atencion/conversaciones/7');
        Http::assertSent(fn ($request): bool => $request->method() === 'POST' && str_ends_with($request->url(), '/7/leer'));
    }

    public function test_cliente_final_no_puede_consultar_el_panel(): void
    {
        $this->withSession(['usuario' => ['id' => 3, 'rol' => 'CLIENTE']])->get('/clientes')->assertForbidden();
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/api/atencion/'));
    }
}
