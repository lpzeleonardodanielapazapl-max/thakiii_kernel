<?php

namespace Tests\Feature;

use App\Http\Middleware\SesionPrivada;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OperacionesInventarioTest extends TestCase
{
    private function resumen(): array
    {
        return ['unidades' => [['id' => 1, 'producto' => 'PRO-1', 'nombre' => 'Carpa', 'categoria' => 'ALPINISMO', 'codigo' => 'CC1', 'color' => 'Verde', 'almacen' => null, 'nombreAlmacen' => 'Ubicación pendiente', 'estadoFisico' => null, 'calificacion' => null, 'fechaBaja' => null, 'prestada' => 0]],
            'stock' => [['producto' => 'PRO-2', 'nombre' => 'Platos', 'categoria' => 'COCINA', 'almacen' => 'Principal', 'cantidad' => 10, 'medida' => 'und', 'ubicacion' => null]],
            'documentos' => [['codigo' => 'REF-1', 'tipo' => 'REF', 'numero' => 'XLS-001', 'fecha' => '2026-11-06', 'grupo' => 'ALPINISMO', 'observaciones' => '{}']], 'precios' => [], 'movimientos' => [], 'prestamos' => []];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_apartados_funcionan_sin_pedir_actualizacion_de_la_base(): void
    {
        $this->withoutMiddleware(SesionPrivada::class);
        Http::fake(['*/api/inventario/operaciones' => Http::response($this->resumen())]);
        foreach (['existencias', 'referencias', 'documentos', 'precios', 'prestamos', 'movimientos'] as $ver) {
            $this->get('/inventario/operaciones?ver='.$ver)->assertOk()->assertDontSeeText('Preparar el inventario operativo')->assertDontSeeText('inventario-01-actualizacion.sql')->assertDontSeeText('Gastos de transporte');
        }
        $this->get('/inventario/operaciones?categoria=COCINA')->assertOk()->assertSeeText('Platos')->assertDontSeeText('Carpa');
    }

    public function test_pantallas_y_acciones_retiradas_no_se_pueden_usar(): void
    {
        $this->withoutMiddleware(SesionPrivada::class);
        foreach (['gastos', 'ingresos', 'salidas', 'traslados', 'inspecciones', 'regularizaciones', 'excel'] as $ver) {
            $this->get('/inventario/operaciones?ver='.$ver)->assertNotFound();
        }
        $this->post('/inventario/operaciones/ingresos', [])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_cliente_no_puede_entrar_al_inventario(): void
    {
        Http::fake(['*/api/acceso/sesion' => Http::response(['rol' => 'CLIENTE'])]);
        $this->withSession(['api_token' => 'cliente'])->get('/inventario/operaciones')->assertForbidden();
        Http::assertSentCount(1);
    }

    public function test_referencia_se_muestra_como_fuente_y_no_como_nota_de_ingreso(): void
    {
        $this->withoutMiddleware(SesionPrivada::class);
        Http::fake(['*/api/inventario/operaciones/documentos/REF-1' => Http::response(['codigo' => 'REF-1', 'tipo' => 'REF', 'numero' => 'XLS-001', 'fecha' => '2026-11-06', 'observaciones' => json_encode(['archivo' => 'Inventarios 2026 (1).xlsx', 'hoja' => 'INV MATERIAL ALPINISMO', 'nota' => 'Referencia sin disponibilidad confirmada.', 'anotaciones' => []]), 'items' => [['descripcion' => 'Carpa', 'cantidad' => 1, 'observaciones' => json_encode(['fila' => 132, 'codigo' => 'CC1', 'color' => 'Verde', 'nota' => 'Resumen 11, detalle 13.'])]]])]);
        $r = $this->get('/inventario/operaciones/documentos/REF-1')->assertOk()->assertSeeText('Resumen 11, detalle 13.')->assertSeeText('No es una nota de ingreso')->assertDontSeeText('Registrar ingreso');
        $this->assertSame(1, substr_count($r->getContent(), 'id="informe-impresion"'));
    }

    public function test_catalogos_existentes_se_editan_sin_nuevas_tablas(): void
    {
        $this->withoutMiddleware(SesionPrivada::class);
        Http::fake(['*/api/inventario/catalogos' => Http::response(['almacenes' => [['codigo' => 'A1', 'nombre' => 'Principal', 'ubicacion' => 'La Paz', 'estado' => 'ACTIVO']], 'clases' => [], 'marcas' => [], 'medidas' => []]), '*/api/inventario/catalogos/almacenes/A1' => Http::response(['codigo' => 'A1'])]);
        $this->get('/inventario/catalogos?editar=A1')->assertOk()->assertSeeText('Editar Principal');
        $this->put('/inventario/catalogos/almacenes/A1', ['nombre' => 'Principal', 'estado' => 'INACTIVO'])->assertRedirect('/inventario/catalogos?tipo=almacenes');
    }
}
