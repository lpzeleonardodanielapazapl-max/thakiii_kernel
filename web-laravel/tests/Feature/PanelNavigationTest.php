<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PanelNavigationTest extends TestCase
{
    private function prepararApi(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/api/inventario/productos' => Http::response([
                ['codigo' => 'PRO-1', 'nombre' => 'Carpa de prueba', 'marca' => 'Doite',
                    'categoria' => 'ALPINISMO', 'unidadesRegistradas' => 2,
                    'unidadesRequierenArreglo' => 1, 'unidadesConBaja' => 0],
            ]),
            '*/api/abc' => Http::response(['productos' => [], 'valorTotal' => 0, 'sinPrecio' => [], 'sinUnidades' => []]),
            '*/api/registro-productos/catalogos' => Http::response(['clases' => [], 'marcas' => [], 'unidadesMedida' => []]),
            '*/api/v1/personas' => Http::response([
                ['codPersona' => 'PER-1', 'nombres' => 'Ana', 'apellidos' => 'Pérez'],
            ]),
            '*/api/v1/empleados' => Http::response([
                ['codEmpleado' => 'EMP-1', 'codPersona' => 'PER-1', 'estado' => 'ACTIVO', 'esGuia' => true],
            ]),
            '*/api/v1/clientes' => Http::response([
                ['codCliente' => 'CLI-1', 'codPersona' => 'PER-1', 'estado' => 'ACTIVO'],
            ]),
            '*/api/v1/usuarios' => Http::response([
                ['idUsuario' => 1, 'email' => 'ana@example.test', 'codRol' => 'ADMIN', 'activo' => true],
            ]),
            '*/api/v1/roles' => Http::response([
                ['codRol' => 'ADMIN', 'desRol' => 'Administrador'],
            ]),
            '*/api/v1/cargos' => Http::response([['codCargo' => 'CAR-1', 'desCargo' => 'Guía']]),
            '*/api/v1/departamentos' => Http::response([['codDep' => 'DEP-1', 'desDep' => 'Operaciones']]),
        ]);
    }

    public function test_todas_las_secciones_comparten_menu_y_regreso_al_panel(): void
    {
        $this->prepararApi();

        foreach (['/', '/admin', '/inventario', '/inventario/abc', '/investigacion-operativa',
            '/inventario/PRO-1/reposicion', '/inventario/productos/nuevo',
            '/personas', '/empleados', '/clientes', '/usuarios', '/roles'] as $ruta) {
            $this->get($ruta)->assertOk()->assertSee('id="navegacion-admin"', false)
                ->assertSeeText('Volver al panel')->assertSeeText('Reposición · EOQ')
                ->assertDontSee('cdn.tailwindcss.com', false);
        }
    }

    public function test_los_enlaces_internos_de_las_secciones_principales_tienen_ruta_get(): void
    {
        $this->prepararApi();

        foreach (['/admin', '/inventario', '/empleados', '/usuarios', '/clientes', '/roles'] as $ruta) {
            $response = $this->get($ruta)->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            foreach ($dom->getElementsByTagName('a') as $link) {
                $path = parse_url($link->getAttribute('href'), PHP_URL_PATH);
                if (! $path || ! str_starts_with($path, '/')) {
                    continue;
                }
                $matched = app('router')->getRoutes()->match(Request::create($path, 'GET'));
                $this->assertContains('GET', $matched->methods(), $path);
            }
        }
    }

    public function test_fallo_de_api_mantiene_navegacion_y_no_aparenta_directorio_vacio(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        foreach (['/personas', '/empleados', '/clientes', '/usuarios', '/roles', '/inventario', '/inventario/abc'] as $ruta) {
            $this->get($ruta)->assertStatus(503)->assertSeeText('No pudimos cargar esta sección.')
                ->assertSeeText('Ir al panel')->assertSeeText('Volver a intentar')
                ->assertDontSeeText('Aún no hay registros');
        }
    }

    public function test_informe_eoq_usa_bolivianos_y_sigue_el_contrato_de_quarkus(): void
    {
        Http::fake(['*/api/io/eoq' => Http::response([
            'loteTeorico' => 6.32, 'loteRecomendado' => 6, 'pedidosAnuales' => 2,
            'diasEntrePedidos' => 180, 'demandaDiaria' => 0.0333, 'puntoReorden' => 0.5,
            'costoCompraAnual' => 10800, 'costoOrdenarAnual' => 200,
            'costoMantenerAnual' => 180, 'costoGestionAnual' => 380, 'costoTotalAnual' => 11180,
        ])]);

        $this->post('/investigacion-operativa/eoq', [
            'demandaAnual' => 12, 'costoPedido' => 100, 'costoMantenimientoAnual' => 60,
            'costoUnitario' => 900, 'plazoEntregaDias' => 15, 'diasAnuales' => 360,
        ])->assertOk()->assertSeeText('BOB')->assertDontSeeText('USD')
            ->assertSee('id="informe-impresion"', false)->assertSee('data-print', false);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/io/eoq'));
    }
}
