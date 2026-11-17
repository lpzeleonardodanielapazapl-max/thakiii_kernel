<?php

namespace Tests\Feature;

use App\Http\Middleware\SesionPrivada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PanelNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(SesionPrivada::class);
    }

    private function prepararApi(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/api/inventario/productos*' => Http::response([
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
            '*/api/atencion/clientes' => Http::response([]),
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

        foreach (['/admin', '/admin/gestion', '/inventario', '/inventario/abc', '/investigacion-operativa',
            '/inventario/PRO-1/reposicion', '/inventario/productos/nuevo',
            '/personas', '/empleados', '/clientes', '/usuarios', '/roles'] as $ruta) {
            $this->get($ruta)->assertOk()->assertSee('id="navegacion-admin"', false)
                ->assertSeeText('Volver al panel')->assertSeeText('Gestión')
                ->assertDontSee('cdn.tailwindcss.com', false);
        }
    }

    public function test_los_enlaces_internos_de_las_secciones_principales_tienen_ruta_get(): void
    {
        $this->prepararApi();

        foreach (['/admin', '/admin/gestion', '/inventario', '/empleados', '/usuarios', '/clientes', '/roles'] as $ruta) {
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

    public function test_inventario_tiene_apartado_propio_y_gestion_reune_personal_y_servicios(): void
    {
        $this->prepararApi();
        $inicio = $this->get('/admin')->assertOk()->assertSeeText('Inventario y almacén')->assertSeeText('Recuento del Excel');
        $this->assertStringNotContainsString('hero-thaki', $inicio->getContent());
        $dom = new \DOMDocument;
        @$dom->loadHTML($inicio->getContent());
        $xpath = new \DOMXPath($dom);
        $menu = $xpath->query('//nav[@id="navegacion-admin"]//a');
        $destinos = [];
        foreach ($menu as $enlace) {
            $destinos[] = parse_url($enlace->getAttribute('href'), PHP_URL_PATH);
        }
        $this->assertSame(['/admin', '/clientes', '/inventario/operaciones', '/admin/gestion'], $destinos);
        $gestion = $this->get('/admin/gestion')->assertOk();
        foreach (['personas.index', 'empleados.index', 'cargos.index', 'departamentos.index', 'usuarios.index', 'roles.index', 'hoteles.index', 'servicios-transporte.index'] as $ruta) {
            $gestion->assertSee(route($ruta));
        }
    }

    public function test_gestion_permanece_disponible_si_falla_la_api_y_situa_la_seccion_actual(): void
    {
        Http::preventStrayRequests();
        $this->get('/admin/gestion')->assertOk()->assertSeeText('Personas y equipo')->assertSeeText('Alojamientos y movilidad')->assertDontSeeText('Vista del viajero');
        Http::assertNothingSent();
        $this->prepararApi();
        $response = $this->get('/empleados')->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $actual = $xpath->query('//nav[@id="navegacion-admin"]//a[@aria-current="page"]');
        $this->assertSame(1, $actual->length);
        $this->assertSame('/admin/gestion', parse_url($actual[0]->getAttribute('href'), PHP_URL_PATH));
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

    public function test_centro_y_gestion_no_incluyen_enlaces_privados_del_cliente(): void
    {
        $this->prepararApi();
        foreach (['ADMIN', 'ADMINISTRATIVO'] as $rol) {
            foreach (['/admin', '/admin/gestion'] as $ruta) {
                $respuesta = $this->withSession(['usuario' => ['rol' => $rol]])->get($ruta)->assertOk();
                $dom = new \DOMDocument;
                @$dom->loadHTML($respuesta->getContent());
                $xpath = new \DOMXPath($dom);
                foreach ($xpath->query('//main//a | //nav[@id="navegacion-admin"]//a') as $link) {
                    $path = parse_url($link->getAttribute('href'), PHP_URL_PATH) ?? '';
                    $this->assertFalse(str_starts_with($path, '/mi-viaje'), $path);
                    $this->assertNotContains($path, ['/alojamientos', '/traslados']);
                }
            }
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
