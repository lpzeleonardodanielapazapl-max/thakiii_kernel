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

    public function test_propuesta_convierte_cada_linea_en_un_dia_y_admite_servicios_opcionales(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 1, 'rol' => 'ADMIN']), '*/api/atencion/propuestas' => Http::response(['codigo' => 'PRP-1'])]);
        $this->from('/atencion/solicitudes/SOL-1')->post('/atencion/solicitudes/SOL-1/propuestas', ['titulo' => 'Ruta', 'descripcion' => 'A medida', 'precio' => '1000.00', 'moneda' => 'USD', 'itinerario' => "Llegada a La Paz\n\nVisita cultural"])->assertRedirect('/atencion/solicitudes/SOL-1')->assertSessionHas('exito');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['solicitud'] === 'SOL-1' && count($r['dias']) === 2 && $r['dias'][1]['dia'] === 2 && $r['dias'][0]['servicios'] === null);
    }

    public function test_administrativo_no_ve_botones_que_requieran_admin(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['id' => 2, 'rol' => 'ADMINISTRATIVO', 'nombre' => 'Asesor']), '*/api/v1/usuarios' => Http::response([['idUsuario' => 1, 'email' => 'admin@example.test', 'codRol' => 'ROL-001', 'activo' => true]]), '*/api/v1/roles' => Http::response([['codRol' => 'ROL-001', 'desRol' => 'ADMIN']])]);
        $this->get('/usuarios')->assertOk()->assertDontSee(route('usuarios.create'))->assertDontSee(route('usuarios.edit', 1))->assertDontSee(route('usuarios.delete', 1));
        $this->get('/usuarios/1/editar')->assertForbidden();
    }
}
