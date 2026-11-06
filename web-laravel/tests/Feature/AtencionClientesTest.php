<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AtencionClientesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withSession(['api_token' => 'token-staff', 'usuario' => ['id' => 1, 'rol' => 'ADMIN', 'nombre' => 'Admin']]);
    }

    private function solicitud(): array
    {
        return ['codigo' => 'SOL-1', 'nombres' => 'Ana', 'apellidos' => 'Pérez', 'email' => 'ana@example.test', 'fechaInicio' => '2027-01-01', 'fechaFin' => '2027-01-03', 'personas' => 2, 'presupuesto' => 1000, 'moneda' => 'USD', 'estado' => 'RECIBIDA', 'intereses' => 'Cultura', 'preferencias' => [], 'propuestas' => [], 'reservas' => []];
    }

    private function solicitudConVersiones(): array
    {
        return [...$this->solicitud(), 'propuestas' => [
            ['codigo' => 'PRP-2', 'titulo' => 'Ruta actual', 'descripcion' => "Descripción actual\ncon detalles", 'precio' => 1100.50, 'moneda' => 'EUR', 'estado' => 'ENVIADA', 'version' => 2, 'dias' => 2,
                'itinerario' => [['dia' => 1, 'descripcion' => 'Llegada actual', 'servicios' => 'Traslado privado'], ['dia' => 2, 'descripcion' => 'Caminata actual', 'servicios' => 'Guía local']]],
            ['codigo' => 'PRP-1', 'titulo' => 'Ruta original', 'descripcion' => 'Descripción original', 'precio' => 1000, 'moneda' => 'USD', 'estado' => 'REEMPLAZADA', 'version' => 1, 'dias' => 1,
                'itinerario' => [['dia' => 1, 'descripcion' => 'Llegada original', 'servicios' => 'Traslado compartido']]],
        ]];
    }

    private function prepararVersiones(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN']), '*/api/atencion/solicitudes/SOL-1' => Http::response($this->solicitudConVersiones())]);
    }

    public function test_formulario_carga_la_ultima_propuesta_completa_para_modificarla(): void
    {
        $this->prepararVersiones();
        $response = $this->get('/atencion/solicitudes/SOL-1')->assertOk()->assertSeeText('Reenviar propuesta al cliente')->assertSeeText('Trabajando sobre la propuesta 2');
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('Ruta actual', $xpath->query('//input[@name="titulo"]')[0]->getAttribute('value'));
        $this->assertSame('1100.5', $xpath->query('//input[@name="precio"]')[0]->getAttribute('value'));
        $this->assertSame('EUR', $xpath->query('//select[@name="moneda"]/option[@selected]')[0]->getAttribute('value'));
        $this->assertSame("Descripción actual\ncon detalles", $xpath->query('//textarea[@name="descripcion"]')[0]->textContent);
        $this->assertSame('Guía local', $xpath->query('//textarea[@name="dias[1][servicios]"]')[0]->textContent);
        $this->assertSame(4, $xpath->query('//*[@data-dias-lista]//textarea')->length);
        $response->assertSeeText('propuesta 3');
        Http::assertNotSent(fn ($r) => $r->method() !== 'GET');
    }

    public function test_se_puede_usar_una_version_anterior_de_la_misma_solicitud_como_base(): void
    {
        $this->prepararVersiones();
        $response = $this->get('/atencion/solicitudes/SOL-1?editar=PRP-1')->assertOk()->assertSeeText('Trabajando sobre la propuesta 1')->assertSeeText('propuesta 3');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('Ruta original', $xpath->query('//input[@name="titulo"]')[0]->getAttribute('value'));
        $this->assertSame('Traslado compartido', $xpath->query('//textarea[@name="dias[0][servicios]"]')[0]->textContent);
        $this->get('/atencion/solicitudes/SOL-1?editar=PRP-AJENA')->assertNotFound();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'PRP-AJENA'));
    }

    public function test_error_de_validacion_conserva_los_cambios_sobre_la_base(): void
    {
        $this->prepararVersiones();
        $this->from('/atencion/solicitudes/SOL-1')->post('/atencion/solicitudes/SOL-1/propuestas', [
            'titulo' => 'Título modificado', 'descripcion' => 'Cambio pedido por el cliente', 'precio' => 1250, 'moneda' => 'BOB',
            'dias' => [['descripcion' => '', 'servicios' => 'Hotel solicitado por el cliente']],
        ])->assertSessionHasErrors('dias.0.descripcion');
        $response = $this->get('/atencion/solicitudes/SOL-1')->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('Título modificado', $xpath->query('//input[@name="titulo"]')[0]->getAttribute('value'));
        $this->assertSame('Hotel solicitado por el cliente', $xpath->query('//textarea[@name="dias[0][servicios]"]')[0]->textContent);
        $this->assertSame('', $xpath->query('//textarea[@name="dias[0][descripcion]"]')[0]->textContent);
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
    }

    public function test_reenvio_guarda_otra_version_y_vuelve_a_la_solicitud_actualizada(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN']), '*/api/atencion/propuestas' => Http::response(['codigo' => 'PRP-3', 'version' => 3])]);
        $this->from('/atencion/solicitudes/SOL-1?editar=PRP-1')->post('/atencion/solicitudes/SOL-1/propuestas', [
            'titulo' => 'Ruta modificada', 'descripcion' => 'Nueva descripción', 'precio' => 1500, 'moneda' => 'USD', 'version' => 99,
            'dias' => [['descripcion' => 'Cambio solicitado', 'servicios' => 'Nuevo hotel']],
        ])->assertRedirect(route('atencion.solicitud', 'SOL-1'))->assertSessionHas('exito', 'Propuesta 3 enviada al cliente. Las versiones anteriores se conservan en el historial.');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/api/atencion/propuestas')
            && $r['solicitud'] === 'SOL-1' && $r['dias'][0]['servicios'] === 'Nuevo hotel' && ! isset($r['version']));
        Http::assertNotSent(fn ($r) => $r->method() === 'PUT' || $r->method() === 'DELETE');
    }

    public function test_atencion_solicitud_conversacion_y_reserva_estan_conectadas(): void
    {
        Http::fake([
            '*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN', 'nombre' => 'Admin']),
            '*/api/atencion/panel' => Http::response(['solicitudes' => [$this->solicitud()], 'conversaciones' => []]),
            '*/api/atencion/solicitudes/SOL-1' => Http::response($this->solicitud()),
            '*/api/atencion/conversaciones/1' => Http::response(['codigo' => 1, 'clienteId' => 7, 'asunto' => 'Viaje', 'mensajes' => []]),
            '*/api/atencion/reservas/RES-1' => Http::response(['codigo' => 'RES-1', 'solicitud' => 'SOL-1', 'cliente' => 'Ana Pérez', 'estado' => 'PENDIENTE_ANTICIPO', 'total' => 1000, 'moneda' => 'USD', 'pagado' => 0, 'pagos' => [], 'cancelacionSolicitada' => false]),
        ]);
        $this->get('/atencion')->assertOk()->assertSee(route('atencion.solicitud', 'SOL-1'));
        $this->get('/atencion/solicitudes/SOL-1')->assertOk()->assertSeeText('Enviar propuesta al cliente');
        $this->get('/atencion/conversaciones/1')->assertOk()->assertSeeText('Responder al cliente');
        $this->get('/atencion/reservas/RES-1')->assertOk()->assertSeeText('Registrar pago verificado')->assertDontSeeText('Registrar cancelación solicitada');
    }

    public function test_pago_exige_confirmacion_de_verificacion_antes_de_enviar_a_quarkus(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN']), '*/api/atencion/reservas/RES-1/pagos' => Http::response(['codigo' => 'PAG-1'])]);
        $datos = ['fecha' => now()->format('Y-m-d'), 'monto' => '500.00', 'metodo' => 'Transferencia', 'referencia' => 'REF-1'];
        $this->from('/atencion/reservas/RES-1')->post('/atencion/reservas/RES-1/pagos', $datos)->assertSessionHasErrors('verificado');
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
        $this->post('/atencion/reservas/RES-1/pagos', [...$datos, 'verificado' => '1'])->assertRedirect('/atencion/reservas/RES-1')->assertSessionHas('exito');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['verificado'] === true && $r->hasHeader('Authorization', 'Bearer token-staff'));
    }

    public function test_propuesta_conserva_los_bloques_y_servicios_independientes_de_cada_dia(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN']), '*/api/atencion/propuestas' => Http::response(['codigo' => 'PRP-1'])]);
        $this->from('/atencion/solicitudes/SOL-1')->post('/atencion/solicitudes/SOL-1/propuestas', ['titulo' => 'Ruta', 'descripcion' => 'A medida', 'precio' => '1000.00', 'moneda' => 'USD', 'dias' => [
            4 => ['descripcion' => "Llegada a La Paz\nTraslado al hotel", 'servicios' => 'Traslado privado'],
            9 => ['descripcion' => 'Visita cultural'],
        ]])->assertRedirect('/atencion/solicitudes/SOL-1')->assertSessionHas('exito');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['solicitud'] === 'SOL-1' && count($r['dias']) === 2
            && $r['dias'][0]['dia'] === 1 && $r['dias'][1]['dia'] === 2 && $r['dias'][0]['servicios'] === 'Traslado privado'
            && $r['dias'][0]['descripcion'] === "Llegada a La Paz\nTraslado al hotel" && $r['dias'][1]['servicios'] === null);
    }

    public function test_dia_incompleto_no_se_envia_y_conserva_los_otros_campos(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN'])]);
        $this->from('/atencion/solicitudes/SOL-1')->post('/atencion/solicitudes/SOL-1/propuestas', ['titulo' => 'Ruta', 'descripcion' => 'A medida', 'precio' => 1000, 'moneda' => 'USD', 'dias' => [['descripcion' => '', 'servicios' => 'Alojamiento']]])
            ->assertSessionHasErrors('dias.0.descripcion')->assertSessionHas('_old_input.dias.0.servicios', 'Alojamiento');
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
    }

    public function test_historial_muestra_el_contenido_de_cada_version_sin_cruzarlo(): void
    {
        $solicitud = $this->solicitud();
        $solicitud['propuestas'] = [
            ['codigo' => 'PRP-2', 'titulo' => 'Ruta actual', 'precio' => 1100, 'moneda' => 'USD', 'estado' => 'ENVIADA', 'version' => 2, 'descripcion' => 'Versión ajustada', 'dias' => 1, 'fechaEnvio' => '2026-11-05T18:00:00', 'itinerario' => [['dia' => 1, 'descripcion' => 'Recorrido nuevo', 'servicios' => 'Hotel nuevo']]],
            ['codigo' => 'PRP-1', 'titulo' => 'Ruta anterior', 'precio' => 1000, 'moneda' => 'USD', 'estado' => 'REEMPLAZADA', 'version' => 1, 'descripcion' => 'Versión original', 'dias' => 1, 'itinerario' => [['dia' => 1, 'descripcion' => 'Recorrido original', 'servicios' => 'Hotel original']]],
        ];
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN']), '*/api/atencion/solicitudes/SOL-1' => Http::response($solicitud)]);
        $response = $this->get('/atencion/solicitudes/SOL-1')->assertOk()->assertSeeText('El viaje de Ana Pérez')->assertSeeText('Historial de propuestas')->assertSeeText('Hotel original')->assertSeeText('Hotel nuevo')->assertSeeText('Versión anterior');
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $versiones = $xpath->query('//details[contains(@class,"version-propuesta")]');
        $this->assertCount(2, $versiones);
        $this->assertStringContainsString('Hotel nuevo', $versiones[0]->textContent);
        $this->assertStringNotContainsString('Hotel original', $versiones[0]->textContent);
        $this->assertSame(1, $xpath->query('//form[contains(@action,"/aceptacion")]')->length);
        $this->assertSame(2, $xpath->query('//*[@data-dias-lista]//textarea')->length);
    }

    public function test_administrativo_no_ve_botones_que_requieran_admin(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 2, 'rol' => 'ADMINISTRATIVO', 'nombre' => 'Asesor']), '*/api/v1/usuarios' => Http::response([['idUsuario' => 1, 'email' => 'admin@example.test', 'codRol' => 'ROL-001', 'activo' => true]]), '*/api/v1/roles' => Http::response([['codRol' => 'ROL-001', 'desRol' => 'ADMIN']])]);
        $this->get('/usuarios')->assertOk()->assertDontSee(route('usuarios.create'))->assertDontSee(route('usuarios.edit', 1))->assertDontSee(route('usuarios.delete', 1));
        $this->get('/usuarios/1/editar')->assertForbidden();
    }
}
