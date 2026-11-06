<?php

namespace Tests\Feature;

use App\Http\Middleware\SesionPrivada;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnidadInventarioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(SesionPrivada::class);
        config(['services.quarkus.url' => 'http://localhost:8080']);
        Http::preventStrayRequests();
    }

    private function datos(): array
    {
        return [
            'codigoUnico' => 'CD001',
            'codAlmacen' => 'ALM-001',
            'estadoFisico' => 'BUENO',
            'fechaAlta' => '2026-01-01',
        ];
    }

    private function ficha(): array
    {
        return [
            'producto' => ['codigo' => 'PRO-1', 'nombre' => 'Carpa Doite', 'estado' => 'ACTIVO'],
            'unidades' => [],
            'almacenes' => [['codigo' => 'ALM-001', 'nombre' => 'Almacén Principal']],
        ];
    }

    public function test_muestra_ficha_y_formulario_con_datos_de_quarkus(): void
    {
        Http::fake(['*/api/inventario/productos/PRO-1/unidades' => Http::response($this->ficha())]);

        $this->get('/inventario/PRO-1/unidades')->assertOk()
            ->assertSeeText('Carpa Doite')->assertSeeText('Registrar una unidad')
            ->assertSeeText('Almacén Principal');
    }

    public function test_envia_json_tipado_y_redirige_despues_del_registro(): void
    {
        Http::fake(['*/unidades' => Http::response(['codigoUnico' => 'CD001'], 201)]);

        $this->post('/inventario/PRO-1/unidades', [
            ...$this->datos(), 'calificacion' => '3', 'requiereArreglo' => '1',
        ])->assertRedirect('/inventario/PRO-1/unidades')->assertSessionHas('exito');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/inventario/productos/PRO-1/unidades'
            && $request['codigoUnico'] === 'CD001' && $request['calificacion'] === 3
            && $request['requiereArreglo'] === true && $request['requiereCambio'] === false
            && $request['requiereBaja'] === false);
    }

    public function test_rechaza_formulario_invalido_sin_llamar_a_la_api(): void
    {
        $this->post('/inventario/PRO-1/unidades', [
            ...$this->datos(), 'codigoUnico' => '', 'calificacion' => -1, 'fechaAlta' => '2099-01-01',
        ])->assertSessionHasErrors(['codigoUnico', 'calificacion', 'fechaAlta']);
        Http::assertNothingSent();
    }

    public function test_conserva_datos_y_muestra_el_codigo_duplicado_reportado_por_quarkus(): void
    {
        Http::fake(['*/unidades' => Http::response(['mensaje' => 'Ya existe una unidad con ese código único.'], 409)]);

        $this->from('/inventario/PRO-1/unidades')
            ->post('/inventario/PRO-1/unidades', $this->datos())
            ->assertRedirect('/inventario/PRO-1/unidades')->assertSessionHasErrors('api')
            ->assertSessionHasInput('codigoUnico', 'CD001');
    }

    public function test_presenta_errores_de_validacion_del_backend(): void
    {
        Http::fake(['*/unidades' => Http::response(['mensaje' => 'Selecciona un almacén activo existente.'], 422)]);

        $this->post('/inventario/PRO-1/unidades', $this->datos())
            ->assertSessionHasErrors(['api' => 'Selecciona un almacén activo existente.']);
    }

    public function test_fallo_de_conexion_no_se_presenta_como_lista_vacia(): void
    {
        Http::fake(['*/unidades' => Http::failedConnection()]);

        $this->get('/inventario/PRO-1/unidades')->assertStatus(503)
            ->assertSeeText('No pudimos consultar las unidades')
            ->assertDontSeeText('todavía no tiene unidades')
            ->assertDontSee('name="codigoUnico"', false);
    }

    public function test_fallo_al_guardar_conserva_el_formulario_sin_mensaje_de_exito(): void
    {
        Http::fake(['*/unidades' => Http::response([], 500)]);

        $this->post('/inventario/PRO-1/unidades', $this->datos())
            ->assertSessionHasErrors('api')->assertSessionMissing('exito')
            ->assertSessionHasInput('codigoUnico', 'CD001');
    }

    public function test_producto_inexistente_devuelve_404(): void
    {
        Http::fake(['*/unidades' => Http::response(['mensaje' => 'Producto no encontrado.'], 404)]);

        $this->get('/inventario/PRO-1/unidades')->assertNotFound();
        $this->post('/inventario/PRO-1/unidades', $this->datos())->assertNotFound();
    }

    public function test_producto_inactivo_no_ofrece_registro(): void
    {
        $ficha = $this->ficha();
        $ficha['producto']['estado'] = 'INACTIVO';
        Http::fake(['*/unidades' => Http::response($ficha)]);

        $this->get('/inventario/PRO-1/unidades')->assertOk()
            ->assertSeeText('El producto está inactivo')->assertDontSee('name="codigoUnico"', false);
    }

    public function test_muestra_unidad_y_distingue_solicitud_de_baja_de_baja_registrada(): void
    {
        $ficha = $this->ficha();
        $ficha['unidades'][] = [
            'id' => 1, 'codigoUnico' => 'CD001', 'almacen' => 'Almacén Principal',
            'estadoFisico' => 'REGULAR', 'calificacion' => null,
            'requiereArreglo' => true, 'requiereCambio' => false, 'requiereBaja' => true,
            'fechaAlta' => '2026-01-01', 'fechaBaja' => null, 'observaciones' => '<script>daño</script>',
        ];
        Http::fake(['*/unidades' => Http::response($ficha)]);

        $this->get('/inventario/PRO-1/unidades')->assertOk()
            ->assertSeeText('CD001')->assertSeeText('Baja solicitada')
            ->assertDontSee('<span class="etiqueta-unidad">Baja registrada</span>', false)
            ->assertSee('&lt;script&gt;daño&lt;/script&gt;', false);
    }
}
