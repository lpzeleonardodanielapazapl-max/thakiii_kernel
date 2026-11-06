<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalendarioPerfilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function ingresar(string $rol = 'CLIENTE'): void
    {
        $usuario = ['id' => 7, 'rol' => $rol, 'nombre' => 'Ana'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario)]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario]);
    }

    public function test_calendario_solo_personal_y_consulta_un_intervalo_acotado_con_token(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::sequence()->push(['id' => 1, 'rol' => 'ADMIN'])->push(['id' => 2, 'rol' => 'ADMINISTRATIVO'])]);
        $this->withSession(['api_token' => 'token']);
        foreach (['ADMIN', 'ADMINISTRATIVO'] as $rol) {
            Http::fake(['*/api/atencion/calendario*' => Http::response([[
                'codigo' => 'VIA-1', 'reserva' => 'RES-1', 'propuesta' => 'PRP-1', 'titulo' => 'Uyuni privado', 'cliente' => 'Ana Pérez',
                'fechaInicio' => '2026-10-31', 'fechaFin' => '2026-11-03', 'personas' => 2, 'estado' => 'PENDIENTE_ANTICIPO', 'version' => 2,
            ]])]);
            $response = $this->get('/atencion/calendario?mes=2026-11')->assertOk()->assertSeeText('noviembre 2026')->assertSeeText('Uyuni privado')->assertSee(route('atencion.reserva', 'RES-1'));
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(42, $xpath->query('//table[contains(@class,"agenda-tabla")]/tbody/tr/td')->length);
            $this->assertSame(4, $xpath->query('//a[contains(@class,"agenda-evento")]')->length);
        }
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/atencion/calendario') && $r['desde'] === '2026-10-26' && $r['hasta'] === '2026-12-06' && $r->hasHeader('Authorization', 'Bearer token'));
    }

    public function test_anonimos_y_clientes_no_pueden_abrir_el_calendario_del_personal(): void
    {
        $this->get('/atencion/calendario')->assertRedirect(route('acceso.ingresar'));
        $this->ingresar();
        $this->get('/atencion/calendario')->assertForbidden();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/api/atencion/calendario'));
    }

    public function test_dia_seleccionado_filtra_la_agenda_y_el_mes_se_valida(): void
    {
        $this->ingresar('ADMINISTRATIVO');
        Http::fake(['*/api/atencion/calendario*' => Http::response([])]);
        $this->get('/atencion/calendario?mes=2026-11&dia=2026-11-04')->assertOk()->assertSeeText('Viajes del 04/11/2026')->assertSeeText('No hay viajes aceptados para este día.');
        $this->get('/atencion/calendario?mes=2026-99')->assertSessionHasErrors('mes');
        $this->get('/atencion/calendario?mes=2019-12')->assertSessionHasErrors('mes');
    }

    public function test_perfil_incompleto_bloquea_formulario_y_post_y_conserva_el_pedido(): void
    {
        $this->ingresar();
        Http::fake(['*/api/cliente/perfil' => Http::response(['completitud' => ['completo' => false, 'porcentaje' => 37]])]);
        $this->get('/mi-viaje/solicitar')->assertRedirect(route('portal.perfil'))->assertSessionHas('continuar_solicitud', true);
        $datos = ['fechaInicio' => now()->addDays(10)->toDateString(), 'fechaFin' => now()->addDays(12)->toDateString(), 'personas' => 2, 'moneda' => 'USD', 'intereses' => 'Caminar y conocer Uyuni'];
        $this->post('/mi-viaje/solicitudes', $datos)->assertRedirect(route('portal.perfil'))->assertSessionHas('viaje_borrador.intereses', $datos['intereses']);
        Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_contains($r->url(), '/api/cliente/solicitudes'));
    }

    public function test_guardar_perfil_completo_retoma_pedido_y_un_guardado_parcial_no_lo_desbloquea(): void
    {
        $this->ingresar();
        Http::fake(['*/api/cliente/perfil' => Http::sequence()->push(['completitud' => ['completo' => false]])->push(['completitud' => ['completo' => true]])]);
        $this->withSession(['continuar_solicitud' => true, 'viaje_borrador' => ['destinos' => 'Uyuni']])->from('/mi-viaje/perfil')->put('/mi-viaje/perfil', ['nombres' => 'Ana', 'apellidos' => 'Pérez'])
            ->assertRedirect('/mi-viaje/perfil')->assertSessionHas('continuar_solicitud', true);
        $this->put('/mi-viaje/perfil', ['nombres' => 'Ana', 'apellidos' => 'Pérez'])->assertRedirect(route('portal.solicitud'))->assertSessionMissing('continuar_solicitud')->assertSessionHas('viaje_borrador.destinos', 'Uyuni');
    }

    public function test_mi_viaje_integra_chat_vertical_y_progreso_sin_mezclar_mensajes(): void
    {
        $this->ingresar();
        Http::fake([
            '*/api/cliente/resumen' => Http::response(['perfil' => ['nombres' => 'Ana', 'completitud' => ['porcentaje' => 37, 'completo' => false]], 'asesor' => ['nombre' => 'Leo'], 'reservas' => [], 'propuestas' => [], 'solicitudes' => [], 'notificaciones' => []]),
            '*/api/cliente/mensajes' => Http::response([['remitente' => 7, 'contenido' => 'Mi idea privada', 'fecha' => '2026-11-06T10:00:00']]),
        ]);
        $response = $this->get('/mi-viaje')->assertOk()->assertSeeText('37 %')->assertSeeText('Mi idea privada')->assertSee('viaje-lateral')->assertSee(route('portal.mensajes.enviar'));
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//aside[contains(@class,"viaje-lateral")]//*[@data-chat]')->length);
        $this->assertSame(1, $xpath->query('//textarea[@name="contenido"]')->length);
    }
}
