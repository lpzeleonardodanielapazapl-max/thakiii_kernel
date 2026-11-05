<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventarioGestionTest extends TestCase
{
    private function producto(): array
    {
        return ['codigo' => 'PRO-1', 'nombre' => 'Carpa Doite', 'codClase' => 'CLA-1', 'codMarca' => null,
            'codUm' => 'UM-1', 'modelo' => 'Andes', 'color' => 'Verde', 'stockMinimo' => 2, 'estado' => 'ACTIVO'];
    }

    private function unidad(): array
    {
        return ['id' => 1, 'codigoUnico' => 'CD001', 'codAlmacen' => 'ALM-1', 'almacen' => 'Principal',
            'estadoFisico' => 'BUENO', 'calificacion' => null, 'fechaAlta' => '2026-01-01', 'fechaBaja' => null,
            'requiereArreglo' => false, 'requiereCambio' => false, 'requiereBaja' => false, 'observaciones' => null];
    }

    public function test_producto_tiene_detalle_y_edicion_con_catalogos(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/api/inventario/productos/PRO-1' => Http::response($this->producto()),
            '*/api/registro-productos/catalogos' => Http::response([
                'clases' => [['codigo' => 'CLA-1', 'nombre' => 'Carpas']], 'marcas' => [],
                'unidadesMedida' => [['codigo' => 'UM-1', 'nombre' => 'Unidad']],
            ]),
        ]);
        $this->get('/inventario/productos/PRO-1')->assertOk()->assertSeeText('Editar producto')->assertSeeText('Carpas');
        $this->get('/inventario/productos/PRO-1/editar')->assertOk()->assertSeeText('Guardar cambios')
            ->assertSee('value="Andes"', false)->assertSeeText('Inactivar conserva');
    }

    public function test_producto_se_inactiva_por_api_sin_eliminar_unidades(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*/api/inventario/productos/PRO-1' => Http::response([...$this->producto(), 'estado' => 'INACTIVO'])]);
        $this->put('/inventario/productos/PRO-1', [...$this->producto(), 'estado' => 'INACTIVO'])
            ->assertRedirect('/inventario/productos/PRO-1')->assertSessionHas('exito');
        Http::assertSent(fn ($r) => $r->method() === 'PUT' && $r['estado'] === 'INACTIVO');
        $this->assertFalse(collect(Http::recorded())->contains(fn ($entry) => $entry[0]->method() === 'DELETE'));
    }

    public function test_inventario_puede_consultar_productos_inactivos(): void
    {
        Http::fake(['*' => Http::response([])]);
        $this->get('/inventario?estado=INACTIVO')->assertOk();
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/api/inventario/productos?estado=INACTIVO'));
    }

    public function test_unidad_se_consulta_y_actualiza_por_su_id_y_producto(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/api/inventario/productos/PRO-1/unidades/1' => Http::response($this->unidad()),
            '*/api/inventario/productos/PRO-1/unidades' => Http::response([
                'producto' => $this->producto(), 'unidades' => [$this->unidad()],
                'almacenes' => [['codigo' => 'ALM-1', 'nombre' => 'Principal']],
            ]),
        ]);
        $this->get('/inventario/PRO-1/unidades/1/editar')->assertOk()->assertSeeText('Editar unidad CD001');
        $this->put('/inventario/PRO-1/unidades/1', [...$this->unidad(), 'requiereArreglo' => '1'])
            ->assertRedirect('/inventario/PRO-1/unidades')->assertSessionHas('exito');
        Http::assertSent(fn ($r) => $r->method() === 'PUT' && $r['requiereArreglo'] === true && $r['requiereBaja'] === false);
    }

    public function test_error_al_editar_unidad_no_se_presenta_como_exito(): void
    {
        Http::fake(['*' => Http::response(['mensaje' => 'El código único ya asignado no puede cambiarse.'], 422)]);
        $this->put('/inventario/PRO-1/unidades/1', $this->unidad())
            ->assertSessionHasErrors('api')->assertSessionMissing('exito');
    }
}
