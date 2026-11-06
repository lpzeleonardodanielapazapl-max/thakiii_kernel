<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlanificadorPublicoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function preferencias(): array
    {
        return ['destinos' => 'Uyuni', 'fechaInicio' => now()->addDays(15)->format('Y-m-d'), 'fechaFin' => now()->addDays(20)->format('Y-m-d'), 'personas' => 3, 'presupuesto' => 2500, 'moneda' => 'USD', 'hotel' => 'Habitación familiar', 'alimentacion' => 'Vegetariana', 'transporte' => 'Privado'];
    }

    public function test_portada_conecta_planificador_y_destinos_con_las_rutas_reales(): void
    {
        Http::fake(['*/api/publico/inicio' => Http::response(['empresa' => [], 'destinos' => [], 'testimonios' => []])]);
        $this->get('/')->assertOk()->assertSee(route('publico.planificar'))->assertSeeText('¿Adónde quieres ir?')
            ->assertSeeText('Otras preferencias')->assertSeeText('Diseñar mi viaje')->assertSee('data-destinos-carrusel', false)
            ->assertDontSeeText('25%')->assertDontSee('all.accor.com');
    }

    public function test_visitante_conserva_preferencias_hasta_el_registro_sin_enviar_solicitudes(): void
    {
        $datos = $this->preferencias();
        $this->get('/planificar-viaje?'.http_build_query($datos))->assertRedirect(route('acceso.registro'))->assertSessionHas('viaje_borrador.destinos', 'Uyuni')->assertSessionHas('viaje_borrador.hotel', 'Habitación familiar');
        Http::assertNothingSent();
    }

    public function test_fechas_invalidas_o_viajeros_negativos_no_guardan_borrador(): void
    {
        $this->from('/')->get('/planificar-viaje?'.http_build_query([...$this->preferencias(), 'fechaFin' => now()->addDay()->format('Y-m-d'), 'personas' => -1]))
            ->assertRedirect('/')->assertSessionHasErrors(['fechaFin', 'personas'])->assertSessionMissing('viaje_borrador');
        Http::assertNothingSent();
    }

    public function test_cliente_continua_con_el_formulario_precargado(): void
    {
        $usuario = ['id' => 7, 'rol' => 'CLIENTE', 'nombre' => 'Ana'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/cliente/perfil' => Http::response(['completitud' => ['completo' => true]])]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->get('/planificar-viaje?'.http_build_query($this->preferencias()))->assertRedirect(route('portal.solicitud'));
        $response = $this->get('/mi-viaje/solicitar')->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        foreach (['destinos', 'fechaInicio', 'fechaFin', 'personas', 'hotel', 'alimentacion', 'transporte'] as $campo) {
            $this->assertSame((string) $this->preferencias()[$campo], $xpath->query('//input[@name="'.$campo.'"]')[0]->getAttribute('value'));
        }
    }

    public function test_ingreso_del_cliente_retoma_su_idea_de_viaje(): void
    {
        Http::fake(['*/api/acceso/ingresar' => Http::response(['token' => 'token', 'usuario' => ['id' => 7, 'rol' => 'CLIENTE']])]);
        $this->withSession(['viaje_borrador' => $this->preferencias()])->post('/ingresar', ['email' => 'ana@example.test', 'password' => 'clave-de-prueba'])
            ->assertRedirect(route('portal.solicitud'))->assertSessionHas('viaje_borrador.destinos', 'Uyuni');
    }

    public function test_borrador_se_limpia_despues_de_guardar_la_solicitud_en_quarkus(): void
    {
        $usuario = ['id' => 7, 'rol' => 'CLIENTE'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/cliente/perfil' => Http::response(['completitud' => ['completo' => true]]), '*/api/cliente/solicitudes' => Http::response(['codigo' => 'SOL-1'])]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario, 'viaje_borrador' => $this->preferencias()])->post('/mi-viaje/solicitudes', [...$this->preferencias(), 'intereses' => 'Naturaleza'])
            ->assertRedirect(route('portal.inicio'))->assertSessionMissing('viaje_borrador');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['destinos'] === 'Uyuni' && $r['personas'] === 3 && $r->hasHeader('Authorization', 'Bearer token'));
    }

    public function test_personal_no_se_mezcla_con_la_planificacion_del_cliente(): void
    {
        $this->withSession(['usuario' => ['rol' => 'ADMIN']])->get('/planificar-viaje?'.http_build_query($this->preferencias()))
            ->assertRedirect(route('publico.inicio'))->assertSessionHasErrors('viaje')->assertSessionMissing('viaje_borrador');
        Http::assertNothingSent();
    }
}
