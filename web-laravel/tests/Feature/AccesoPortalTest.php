<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccesoPortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function identidad(string $rol = 'CLIENTE'): array
    {
        return ['id' => 7, 'email' => 'ana@example.test', 'nombre' => 'Ana', 'rol' => $rol];
    }

    private function resumen(): array
    {
        return ['perfil' => ['nombres' => 'Ana'], 'cliente' => [], 'asesor' => [], 'solicitudes' => [], 'propuestas' => [], 'reservas' => [], 'notificaciones' => []];
    }

    public function test_web_publica_es_independiente_del_panel_interno_y_no_copia_rastreadores(): void
    {
        Http::fake(['*/api/publico/inicio' => Http::response(['empresa' => [], 'destinos' => [], 'testimonios' => []])]);
        $this->get('/')->assertOk()->assertSeeText('Cada viajero,')->assertSeeText('Diseña tu viaje')
            ->assertDontSee('navegacion-admin')->assertDontSee('googletagmanager')->assertDontSee('fbq(');
        $this->get('/inspiracion/uyuni')->assertOk()->assertSeeText('Salar de Uyuni');
        $this->get('/inspiracion/inexistente')->assertNotFound();
    }

    public function test_ninguna_ruta_privada_permite_acceso_anonimo(): void
    {
        foreach (['/admin', '/admin/gestion', '/personas', '/usuarios', '/inventario', '/atencion', '/mi-viaje', '/mi-viaje/perfil', '/mi-viaje/mensajes'] as $ruta) {
            $this->get($ruta)->assertRedirect(route('acceso.ingresar'));
        }
        Http::assertNothingSent();
    }

    public function test_login_guarda_token_en_sesion_y_redirige_segun_rol(): void
    {
        Http::fake(['*/api/acceso/ingresar' => Http::sequence()
            ->push(['token' => 'token-privado', 'usuario' => $this->identidad('CLIENTE')])
            ->push(['token' => 'token-privado', 'usuario' => $this->identidad('ADMIN')])
            ->push(['token' => 'token-privado', 'usuario' => $this->identidad('ADMINISTRATIVO')])]);
        foreach (['CLIENTE' => 'portal.inicio', 'ADMIN' => 'admin.index', 'ADMINISTRATIVO' => 'admin.index'] as $rol => $ruta) {
            $this->post('/ingresar', ['email' => 'ana@example.test', 'password' => 'una-clave-segura'])
                ->assertRedirect(route($ruta))->assertSessionHas('api_token', 'token-privado')->assertSessionHas('usuario.rol', $rol);
        }
    }

    public function test_login_fallido_no_guarda_ni_vuelve_a_mostrar_la_clave(): void
    {
        Http::fake(['*/api/acceso/ingresar' => Http::response(['mensaje' => 'Credenciales incorrectas.'], 401)]);
        $this->from('/ingresar')->post('/ingresar', ['email' => 'ana@example.test', 'password' => 'mi-clave-secreta'])
            ->assertRedirect('/ingresar')->assertSessionHasErrors('acceso')->assertSessionMissing('api_token')
            ->assertSessionMissing('_old_input.password')->assertSessionHas('_old_input.email', 'ana@example.test');
    }

    public function test_registro_envia_datos_y_nunca_un_rol_elegido_por_el_visitante(): void
    {
        Http::fake(['*/api/acceso/registrar' => Http::response(['token' => 'token', 'usuario' => $this->identidad()])]);
        $this->post('/registro', ['nombres' => 'Ana', 'apellidos' => 'Pérez', 'email' => 'ana@example.test', 'password' => 'mi-clave-segura', 'password_confirmation' => 'mi-clave-segura', 'rol' => 'ADMIN'])
            ->assertRedirect(route('portal.inicio'));
        Http::assertSent(fn ($r) => $r['email'] === 'ana@example.test' && ! isset($r['rol']));
    }

    public function test_cliente_y_personal_no_pueden_intercambiar_paneles(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::sequence()->push($this->identidad())->push($this->identidad('ADMIN'))]);
        $this->withSession(['api_token' => 'token', 'usuario' => $this->identidad()])->get('/admin')->assertForbidden();
        $this->withSession(['api_token' => 'token', 'usuario' => $this->identidad('ADMIN')])->get('/mi-viaje')->assertForbidden();
    }

    public function test_sesion_vencida_se_elimina_y_fallo_de_servicio_no_aparenta_datos_vacios(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::sequence()->push([], 401)->pushStatus(503)]);
        $this->withSession(['api_token' => 'vencido'])->get('/mi-viaje')->assertRedirect(route('acceso.ingresar'))->assertSessionMissing('api_token');
        $this->withSession(['api_token' => 'token'])->get('/mi-viaje')->assertStatus(503)->assertSeeText('No pudimos conectar');
    }

    public function test_todas_las_pantallas_del_cliente_renderizan_y_envian_bearer(): void
    {
        Http::fake([
            '*/api/acceso/sesion' => Http::response($this->identidad()),
            '*/api/cliente/resumen' => Http::response($this->resumen()),
            '*/api/cliente/perfil' => Http::response(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'email' => 'ana@example.test', 'completitud' => ['completo' => true, 'porcentaje' => 100]]),
            '*/api/cliente/mensajes' => Http::response([]), '*/api/cliente/notificaciones' => Http::response([]),
        ]);
        foreach (['/mi-viaje', '/mi-viaje/perfil', '/mi-viaje/solicitar', '/mi-viaje/mensajes', '/mi-viaje/notificaciones'] as $ruta) {
            $this->withSession(['api_token' => 'token', 'usuario' => $this->identidad()])->get($ruta)->assertOk()->assertSeeText('Mi viaje');
        }
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/api/cliente/resumen') && $r->hasHeader('Authorization', 'Bearer token'));
    }

    public function test_propuesta_tiene_informe_limpio_y_moneda_correcta(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response($this->identidad()), '*/api/cliente/propuestas/PRP-1' => Http::response([
            'codigo' => 'PRP-1', 'titulo' => 'Mi ruta', 'descripcion' => '<script>alert(1)</script>', 'dias' => 1, 'precio' => 1234.50, 'moneda' => 'USD', 'estado' => 'ENVIADA', 'version' => 1,
            'itinerario' => [['dia' => 1, 'descripcion' => 'Llegada a La Paz', 'servicios' => 'Traslado']],
        ])]);
        $response = $this->withSession(['api_token' => 'token', 'usuario' => $this->identidad()])->get('/mi-viaje/propuestas/PRP-1')->assertOk()
            ->assertSee('1.234,50 USD')->assertDontSee('BOB')->assertDontSee('<script>alert(1)</script>', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('/html/body/article[@id="informe-impresion"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="informe-impresion"]//button | //*[@id="informe-impresion"]//form')->length);
    }

    public function test_bootstrap_solo_se_ofrece_localmente_y_se_cierra_si_ya_existe_admin(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        Http::fake(['*/api/acceso/configuracion' => Http::sequence()->push(['configurarAdministrador' => true])->push(['configurarAdministrador' => false])]);
        $this->get('/configurar-administracion')->assertOk()->assertSeeText('Crear administrador');
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])->get('/configurar-administracion')->assertNotFound();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/configurar-administracion')->assertNotFound();
    }

    public function test_salir_invalida_la_sesion_y_revoca_el_token(): void
    {
        Http::fake(['*/api/acceso/salir' => Http::response([], 204)]);
        $this->withSession(['api_token' => 'token', 'usuario' => $this->identidad()])->post('/salir')
            ->assertRedirect(route('publico.inicio'))->assertSessionMissing('api_token')->assertSessionMissing('usuario');
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer token'));
    }

    public function test_reserva_confirmada_destaca_sin_seguir_pidiendo_verificar_el_anticipo(): void
    {
        $resumen = $this->resumen();
        $resumen['reservas'] = [['codigo' => 'RES-1', 'fechaInicio' => '2027-01-01', 'fechaFin' => '2027-01-03', 'personas' => 2, 'estado' => 'CONFIRMADA', 'total' => 1000, 'anticipo' => 500, 'pagado' => 500, 'saldo' => 500, 'moneda' => 'USD']];
        $resumen['propuestas'] = [['codigo' => 'PRP-1', 'titulo' => 'Primera versión', 'dias' => 3, 'version' => 1, 'precio' => 900, 'moneda' => 'USD', 'estado' => 'REEMPLAZADA']];
        Http::fake(['*/api/acceso/sesion' => Http::response($this->identidad()), '*/api/cliente/resumen' => Http::response($resumen), '*/api/cliente/mensajes' => Http::response([])]);
        $response = $this->withSession(['api_token' => 'token', 'usuario' => $this->identidad()])->get('/mi-viaje')->assertOk()
            ->assertSeeText('Tu viaje está confirmado.')->assertSeeText('Versiones anteriores (1)')
            ->assertDontSeeText('El personal debe verificarlo para confirmar el viaje.')->assertDontSeeText('CONFIRMADA');
        $this->assertLessThan(strpos($response->getContent(), 'Tus propuestas'), strpos($response->getContent(), 'Reservas y pagos registrados'));
    }

    public function test_versiones_anteriores_y_aceptadas_no_invitan_a_aceptarlas_nuevamente(): void
    {
        $base = ['titulo' => 'Mi ruta', 'descripcion' => 'Descripción', 'dias' => 1, 'precio' => 1000, 'moneda' => 'USD', 'version' => 1, 'itinerario' => []];
        Http::fake([
            '*/api/acceso/sesion' => Http::response($this->identidad()),
            '*/api/cliente/propuestas/PRP-1' => Http::response([...$base, 'codigo' => 'PRP-1', 'estado' => 'REEMPLAZADA']),
            '*/api/cliente/propuestas/PRP-2' => Http::response([...$base, 'codigo' => 'PRP-2', 'estado' => 'ACEPTADA']),
        ]);
        $this->withSession(['api_token' => 'token', 'usuario' => $this->identidad()])->get('/mi-viaje/propuestas/PRP-1')->assertOk()
            ->assertSeeText('Esta es una versión anterior')->assertDontSeeText('Comunicar mi aceptación');
        $this->get('/mi-viaje/propuestas/PRP-2')->assertOk()->assertSeeText('Tu aceptación ya está registrada.')
            ->assertDontSeeText('Comunicar mi aceptación');
    }
}
