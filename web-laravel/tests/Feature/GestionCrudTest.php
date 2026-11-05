<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GestionCrudTest extends TestCase
{
    private function registros(): array
    {
        return [
            'personas' => ['codPersona' => 'PER-1', 'nombres' => 'Ana', 'apellidos' => 'Pérez', 'email' => 'ana@example.test'],
            'empleados' => ['codEmpleado' => 'EMP-1', 'codPersona' => 'PER-1', 'codCargo' => 'CAR-1', 'codDep' => 'DEP-1', 'fechaIngreso' => '2026-01-01', 'estado' => 'ACTIVO', 'esGuia' => true, 'esAsesor' => false, 'esConductor' => false],
            'clientes' => ['codCliente' => 'CLI-1', 'codPersona' => 'PER-1', 'estado' => 'ACTIVO'],
            'usuarios' => ['idUsuario' => 1, 'email' => 'ana@example.test', 'codRol' => 'ROL-1', 'activo' => true],
            'cargos' => ['codCargo' => 'CAR-1', 'desCargo' => 'Guía', 'descripcion' => 'Guiar grupos en montaña.'],
            'departamentos' => ['codDep' => 'DEP-1', 'desDep' => 'Operaciones', 'descripcion' => 'Planificar salidas.'],
            'roles' => ['codRol' => 'ROL-1', 'desRol' => 'ADMIN', 'descripcion' => 'Administración'],
        ];
    }

    private function preparar(): void
    {
        Http::preventStrayRequests();
        $registros = $this->registros();
        Http::fake(function ($request) use ($registros) {
            foreach ($registros as $modulo => $registro) {
                $key = config('gestion.'.$modulo.'.key');
                $path = parse_url($request->url(), PHP_URL_PATH);
                if ($path === '/api/v1/'.$modulo) {
                    return Http::response($request->method() === 'GET' ? [$registro] : $registro, $request->method() === 'POST' ? 201 : 200);
                }
                if ($path === '/api/v1/'.$modulo.'/'.$registro[$key]) {
                    return Http::response($request->method() === 'DELETE' ? null : $registro, $request->method() === 'DELETE' ? 204 : 200);
                }
            }
            throw new \RuntimeException('Solicitud inesperada: '.$request->url());
        });
    }

    public function test_formularios_detalles_y_edicion_de_todos_los_directorios(): void
    {
        $this->preparar();
        foreach ($this->registros() as $modulo => $registro) {
            $codigo = $registro[config('gestion.'.$modulo.'.key')];
            $this->get('/'.$modulo)->assertOk()->assertSeeText('Ver detalles');
            $this->get('/'.$modulo.'/'.$codigo)->assertOk()->assertSeeText('Datos y detalles');
            $this->get('/'.$modulo.'/'.$codigo.'/editar')->assertOk()->assertSeeText('Guardar cambios');
            if ($modulo !== 'roles') {
                $this->get('/'.$modulo.'/nuevo')->assertOk()->assertSeeText('Guardar registro');
                $this->get('/'.$modulo.'/'.$codigo.'/eliminar')->assertOk()->assertSeeText('Confirma la eliminación');
            }
        }
    }

    public function test_crea_actualiza_y_elimina_mediante_quarkus(): void
    {
        $this->preparar();
        foreach ($this->registros() as $modulo => $registro) {
            $codigo = $registro[config('gestion.'.$modulo.'.key')];
            $datos = $registro;
            if ($modulo === 'usuarios') {
                $datos['password'] = $datos['password_confirmation'] = 'ClaveDePrueba123!';
            }
            if ($modulo !== 'roles') {
                $this->post('/'.$modulo, $datos)->assertRedirect('/'.$modulo.'/'.$codigo)->assertSessionHas('exito');
            }
            $this->put('/'.$modulo.'/'.$codigo, $datos)->assertRedirect('/'.$modulo.'/'.$codigo)->assertSessionHas('exito');
            if ($modulo !== 'roles') {
                $this->delete('/'.$modulo.'/'.$codigo, ['confirmar' => '1'])->assertRedirect('/'.$modulo)->assertSessionHas('exito');
            }
        }
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/empleados') && $r['esGuia'] === true);
    }

    public function test_formulario_de_empleado_ofrece_cargo_departamento_y_funciones(): void
    {
        $this->preparar();
        $this->get('/empleados/nuevo')->assertOk()->assertSeeText('Cargo o trabajo')
            ->assertSeeText('Departamento o área')->assertSeeText('Trabaja como guía')
            ->assertSeeText('Atiende clientes como asesor')->assertSeeText('Trabaja como conductor')
            ->assertSeeText('Fecha de ingreso')->assertSeeText('Salario en BOB')
            ->assertSeeText('Ana Pérez')->assertSeeText('Operaciones');
    }

    public function test_registrar_persona_puede_continuar_al_alta_de_empleado(): void
    {
        $this->preparar();
        $this->post('/personas', ['nombres' => 'Ana', 'apellidos' => 'Pérez', 'volver' => 'empleados'])
            ->assertRedirect('/empleados/nuevo?persona=PER-1');
    }

    public function test_errores_de_quarkus_conservan_datos_pero_no_contrasenas(): void
    {
        Http::fake(['*' => Http::response(['mensaje' => 'Ya existe ese correo.'], 409)]);
        $this->from('/usuarios/nuevo')->post('/usuarios', [
            'email' => 'ana@example.test', 'codRol' => 'ROL-1', 'activo' => '1',
            'password' => 'ClaveDePrueba123!', 'password_confirmation' => 'ClaveDePrueba123!',
        ])->assertRedirect('/usuarios/nuevo')->assertSessionHasErrors(['api' => 'Ya existe ese correo.'])
            ->assertSessionHasInput('email', 'ana@example.test');
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
    }

    public function test_eliminar_exige_confirmacion_y_respeta_vinculos(): void
    {
        Http::preventStrayRequests();
        $this->delete('/empleados/EMP-1')->assertSessionHasErrors('confirmar');
        Http::assertNothingSent();

        Http::fake(['*' => Http::response(['mensaje' => 'El registro tiene información vinculada.'], 409)]);
        $this->delete('/empleados/EMP-1', ['confirmar' => '1'])
            ->assertSessionHasErrors(['api' => 'El registro tiene información vinculada.']);
    }

    public function test_roles_no_se_pueden_crear_ni_eliminar_desde_la_web(): void
    {
        Http::preventStrayRequests();
        $this->get('/roles/nuevo')->assertStatus(405);
        $this->post('/roles', ['desRol' => 'NUEVO'])->assertStatus(405);
        $this->delete('/roles/ROL-1', ['confirmar' => '1'])->assertStatus(405);
        Http::assertNothingSent();
    }

    public function test_fechas_invalidas_y_salario_negativo_no_llegan_a_quarkus(): void
    {
        Http::preventStrayRequests();
        $this->post('/empleados', [
            ...$this->registros()['empleados'], 'fechaRetiro' => '2025-12-01', 'salario' => -1,
        ])->assertSessionHasErrors(['fechaRetiro', 'salario']);
        Http::assertNothingSent();
    }

    public function test_editar_usuario_sin_clave_no_envia_una_clave_vacia(): void
    {
        $this->preparar();
        $this->put('/usuarios/1', $this->registros()['usuarios'])->assertRedirect('/usuarios/1');
        Http::assertSent(fn ($r) => $r->method() === 'PUT' && ! array_key_exists('password', $r->data()));
    }
}
