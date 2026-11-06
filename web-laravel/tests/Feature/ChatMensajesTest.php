<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatMensajesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function mensajes(): array
    {
        return [
            ['id' => 2, 'remitente' => 2, 'contenido' => "Mi respuesta\ncon otro párrafo", 'fecha' => '2026-11-05T10:15:00'],
            ['id' => 1, 'remitente' => 7, 'contenido' => '<script>alert(1)</script>', 'fecha' => '2026-11-04T09:30:00'],
        ];
    }

    public function test_chat_del_asesor_ordena_mensajes_y_distingue_el_remitente_sin_exponer_html(): void
    {
        $usuario = ['id' => 2, 'rol' => 'ADMINISTRATIVO', 'nombre' => 'Asesor'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/atencion/conversaciones/1' => Http::response([
            'codigo' => 1, 'clienteId' => 7, 'asunto' => 'Ruta a medida', 'cliente' => 'Ana Pérez', 'estado' => 'ABIERTA', 'mensajes' => $this->mensajes(),
        ])]);
        $response = $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->get('/atencion/conversaciones/1')->assertOk()
            ->assertSeeText('Ana Pérez')->assertSeeText('10:15')->assertSeeText('09:30')->assertSee('data-chat-form', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $burbujas = $xpath->query('//*[@data-chat-message]');
        $this->assertCount(2, $burbujas);
        $this->assertStringNotContainsString('chat-burbuja-propia', $burbujas[0]->getAttribute('class'));
        $this->assertStringContainsString('chat-burbuja-propia', $burbujas[1]->getAttribute('class'));
        $this->assertStringContainsString("Mi respuesta\ncon otro párrafo", $burbujas[1]->textContent);
        $this->assertSame(1, $xpath->query('//form[contains(@action,"/atencion/conversaciones/1/mensajes")]')->length);
    }

    public function test_cliente_ve_el_mismo_chat_con_su_propio_remitente_y_asesor(): void
    {
        $usuario = ['id' => 7, 'rol' => 'CLIENTE', 'nombre' => 'Ana'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/cliente/mensajes' => Http::response($this->mensajes()), '*/api/cliente/resumen' => Http::response([
            'asesor' => ['id' => 2, 'nombre' => 'Leo Asesor'],
        ])]);
        $response = $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->get('/mi-viaje/mensajes')->assertOk()->assertSeeText('Leo Asesor')->assertSee('chat-pagina-cliente');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $propias = $xpath->query('//*[@data-chat-message and contains(@class,"chat-burbuja-propia")]');
        $this->assertCount(1, $propias);
        $this->assertStringContainsString('<script>alert(1)</script>', $propias[0]->textContent);
        $this->assertSame(1, $xpath->query('//form[contains(@action,"/mi-viaje/mensajes")]')->length);
    }

    public function test_conversacion_cerrada_conserva_historial_y_no_muestra_formulario_de_respuesta(): void
    {
        $usuario = ['id' => 2, 'rol' => 'ADMINISTRATIVO'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/atencion/conversaciones/1' => Http::response([
            'codigo' => 1, 'clienteId' => 7, 'asunto' => 'Ruta', 'cliente' => 'Ana', 'estado' => 'CERRADA', 'mensajes' => $this->mensajes(),
        ])]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->get('/atencion/conversaciones/1')->assertOk()->assertSeeText('Esta conversación está cerrada.')
            ->assertSeeText('Mi respuesta')->assertDontSee('data-chat-form', false);
    }

    public function test_cliente_sin_asesor_tiene_estado_de_espera_y_puede_pedir_asignacion(): void
    {
        $usuario = ['id' => 7, 'rol' => 'CLIENTE'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/cliente/mensajes' => Http::response([]), '*/api/cliente/resumen' => Http::response(['asesor' => []])]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->get('/mi-viaje/mensajes')->assertOk()->assertSeeText('Pendiente de asignación')
            ->assertSeeText('Buscar asesor disponible')->assertDontSee('data-chat-form', false);
    }

    public function test_envio_sigue_usando_la_api_privada_y_rechaza_mensajes_vacios(): void
    {
        $usuario = ['id' => 2, 'rol' => 'ADMINISTRATIVO'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/atencion/conversaciones/1/mensajes' => Http::response([], 204)]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->from('/atencion/conversaciones/1')->post('/atencion/conversaciones/1/mensajes', ['contenido' => '   '])->assertSessionHasErrors('contenido');
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
        $this->post('/atencion/conversaciones/1/mensajes', ['contenido' => 'Hola, revisamos tu ruta.'])->assertRedirect('/atencion/conversaciones/1')->assertSessionHas('exito');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['contenido'] === 'Hola, revisamos tu ruta.' && $r->hasHeader('Authorization', 'Bearer token'));
    }
}
