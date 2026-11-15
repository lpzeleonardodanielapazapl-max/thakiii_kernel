<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogosViajeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public static function roles(): array
    {
        return [['ADMIN'], ['ADMINISTRATIVO']];
    }

    private function registros(): array
    {
        return [
            'hoteles' => ['codigo' => 'HOT-1', 'nombre' => 'Hotel de prueba', 'ciudad' => 'La Paz', 'pais' => 'Bolivia', 'direccion' => 'Centro', 'categoria' => 4, 'estado' => 'ACTIVO', 'checkIn' => '14:00', 'checkOut' => '11:00'],
            'habitaciones' => ['codigo' => 'HAB-1', 'codHotel' => 'HOT-1', 'nombre' => 'Doble', 'capacidad' => 2, 'numCamas' => 1, 'estado' => 'ACTIVO'],
            'tarifas-hotel' => ['codigo' => 'TAR-1', 'codTipoHab' => 'HAB-1', 'fechaInicio' => '2027-01-01', 'fechaFin' => null, 'precioNoche' => 150, 'moneda' => 'BOB', 'incluyeDesayuno' => true],
            'vehiculos' => ['codigo' => 'VEH-1', 'placa' => 'ABC123', 'marca' => 'Toyota', 'modelo' => 'Hiace', 'capacidad' => 8, 'tipo' => 'Van', 'estado' => 'DISPONIBLE'],
            'conductores' => ['codigo' => 'CON-1', 'codEmpleado' => 'EMP-1', 'licencia' => 'LIC-1', 'categoriaLic' => 'C', 'vencimientoLic' => now()->addYear()->toDateString(), 'experienciaAnios' => 3, 'estado' => 'ACTIVO'],
            'servicios-transporte' => ['codigo' => 'TRS-1', 'nombre' => 'Aeropuerto al hotel', 'tipo' => 'Privado', 'origen' => 'Aeropuerto', 'destino' => 'Hotel', 'precioBase' => 100, 'moneda' => 'BOB', 'capacidadMax' => 4, 'estado' => 'ACTIVO'],
        ];
    }

    private function preparar(string $rol): void
    {
        $usuario = ['id' => 2, 'rol' => $rol, 'nombre' => 'Equipo'];
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario]);
        $registros = [...$this->registros(), 'empleados' => ['codEmpleado' => 'EMP-1', 'codPersona' => 'PER-1'], 'personas' => ['codPersona' => 'PER-1', 'nombres' => 'Ana', 'apellidos' => 'Pérez']];
        Http::fake(function ($r) use ($usuario, $registros) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            if ($path === '/api/acceso/sesion') {
                return Http::response($usuario);
            }
            foreach ($registros as $modulo => $registro) {
                if ($path === '/api/v1/'.$modulo) {
                    return Http::response($r->method() === 'GET' ? [$registro] : $registro, $r->method() === 'POST' ? 201 : 200);
                }
                if (str_starts_with($path, '/api/v1/'.$modulo.'/')) {
                    return Http::response($registro);
                }
            }
            throw new \RuntimeException('Petición inesperada: '.$path);
        });
    }

    #[DataProvider('roles')]
    public function test_personal_puede_consultar_crear_y_editar_los_seis_catalogos(string $rol): void
    {
        $this->preparar($rol);
        foreach ($this->registros() as $modulo => $registro) {
            $this->get('/'.$modulo)->assertOk()->assertSeeText(config('gestion.'.$modulo.'.title'));
            $this->get('/'.$modulo.'/nuevo')->assertOk()->assertSeeText('Guardar registro');
            $this->get('/'.$modulo.'/'.$registro['codigo'].'/editar')->assertOk()->assertSeeText('Guardar cambios');
            $this->get('/'.$modulo.'/'.$registro['codigo'])->assertOk()->assertSee('informe-impresion')->assertDontSeeText('Eliminar registro');
            $this->post('/'.$modulo, $registro)->assertRedirect(route($modulo.'.show', $registro['codigo']));
            $this->put('/'.$modulo.'/'.$registro['codigo'], $registro)->assertRedirect(route($modulo.'.show', $registro['codigo']));
            $this->delete('/'.$modulo.'/'.$registro['codigo'], ['confirmar' => 1])->assertStatus(405);
        }
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v1/hoteles') && $r->method() === 'POST' && $r['nombre'] === 'Hotel de prueba' && $r->hasHeader('Authorization', 'Bearer token'));
    }

    public function test_cliente_no_puede_gestionar_catalogos_ni_personal(): void
    {
        $this->preparar('CLIENTE');
        foreach (['hoteles', 'habitaciones', 'tarifas-hotel', 'vehiculos', 'conductores', 'servicios-transporte', 'empleados'] as $modulo) {
            $this->get('/'.$modulo)->assertForbidden();
            $this->post('/'.$modulo, [])->assertForbidden();
        }
        $this->get('/atencion/catalogo')->assertForbidden();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/api/v1/'));
    }

    public function test_consultar_catalogo_conserva_las_preferencias_y_el_texto_sin_crear_solicitud(): void
    {
        $usuario = ['id' => 7, 'rol' => 'CLIENTE'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario)]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->post('/mi-viaje/preferencias', ['catalogo' => 'hotel', 'personas' => 3, 'intereses' => 'Naturaleza y tranquilidad', 'tipoHotel' => 'Superior / 4 estrellas', 'tipoTransporte' => 'Privado'])
            ->assertRedirect(route('publico.alojamientos'))->assertSessionHas('viaje_borrador.intereses', 'Naturaleza y tranquilidad')->assertSessionHas('viaje_borrador.tipoHotel', 'Superior / 4 estrellas');
        Http::assertNotSent(fn ($r) => $r->method() !== 'GET');
    }

    public function test_solicitud_envia_los_tipos_al_backend_y_el_formulario_los_recupera(): void
    {
        $usuario = ['id' => 7, 'rol' => 'CLIENTE'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/cliente/perfil' => Http::response(['completitud' => ['completo' => true]]), '*/api/cliente/solicitudes' => Http::response(['codigo' => 'SOL-1'])]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario, 'viaje_borrador' => ['tipoHotel' => 'Lodge o refugio', 'tipoTransporte' => 'Fluvial']])->get('/mi-viaje/solicitar')->assertOk()->assertSee('value="Lodge o refugio" selected', false)->assertSee('value="Fluvial" selected', false);
        $this->post('/mi-viaje/solicitudes', ['fechaInicio' => now()->addDays(10)->toDateString(), 'fechaFin' => now()->addDays(12)->toDateString(), 'personas' => 2, 'moneda' => 'BOB', 'intereses' => 'Naturaleza', 'tipoHotel' => 'Lodge o refugio', 'tipoTransporte' => 'Fluvial'])->assertRedirect(route('portal.inicio'));
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['tipoHotel'] === 'Lodge o refugio' && $r['tipoTransporte'] === 'Fluvial');
        $this->post('/mi-viaje/preferencias', ['catalogo' => 'hotel', 'tipoHotel' => 'NO_VALIDO'])->assertSessionHasErrors('tipoHotel');
    }

    public function test_editor_del_asesor_consulta_catalogos_reales_con_su_token(): void
    {
        $usuario = ['id' => 2, 'rol' => 'ADMINISTRATIVO'];
        Http::fake(['*/api/acceso/sesion' => Http::response($usuario), '*/api/publico/hospedaje' => Http::response([['nombre' => 'Hotel de prueba']]), '*/api/publico/transporte' => Http::response(['servicios' => [['nombre' => 'Traslado de prueba']], 'vehiculos' => []])]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->get('/atencion/catalogo')->assertOk()->assertJsonPath('hoteles.0.nombre', 'Hotel de prueba')->assertJsonPath('servicios.0.nombre', 'Traslado de prueba');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/publico/hospedaje') && $r->hasHeader('Authorization', 'Bearer token'));
    }
}
