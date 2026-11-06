<?php

namespace Tests\Feature;

use App\Http\Middleware\SesionPrivada;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrintFormattingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(SesionPrivada::class);
    }

    private function fixtures(): void
    {
        Http::preventStrayRequests();
        $registros = [
            'personas' => ['codPersona' => 'PER-1', 'nombres' => 'Ana', 'apellidos' => 'Pérez'],
            'empleados' => ['codEmpleado' => 'EMP-1', 'codPersona' => 'PER-1', 'codCargo' => 'CAR-1', 'codDep' => 'DEP-1',
                'fechaIngreso' => '2026-11-05', 'fechaRetiro' => null, 'salario' => 3333, 'estado' => 'ACTIVO',
                'esGuia' => false, 'esAsesor' => true, 'esConductor' => false, 'createdAt' => '2026-11-05T16:04:09'],
            'clientes' => ['codCliente' => 'CLI-1', 'codPersona' => 'PER-1', 'estado' => 'ACTIVO'],
            'usuarios' => ['idUsuario' => 1, 'email' => 'ana@example.test', 'codRol' => 'ROL-1', 'activo' => true, 'passwordHash' => 'secreto-no-imprimible'],
            'cargos' => ['codCargo' => 'CAR-1', 'desCargo' => 'Guía', 'descripcion' => str_repeat('Coordinar el equipo y revisar el itinerario. ', 40)],
            'departamentos' => ['codDep' => 'DEP-1', 'desDep' => 'Operaciones', 'descripcion' => 'Planificar salidas.'],
            'roles' => ['codRol' => 'ROL-1', 'desRol' => 'ADMIN', 'descripcion' => 'Administración'],
        ];
        $producto = ['codigo' => 'PRO-1', 'nombre' => 'Carpa Doite', 'codClase' => 'CLA-1', 'codMarca' => null, 'codUm' => 'UM-1',
            'modelo' => null, 'color' => null, 'stockMinimo' => 2, 'estado' => 'ACTIVO'];
        Http::fake(function ($request) use ($registros, $producto) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            foreach ($registros as $modulo => $registro) {
                if ($path === '/api/v1/'.$modulo) {
                    return Http::response([$registro]);
                }
                if ($path === '/api/v1/'.$modulo.'/'.$registro[config('gestion.'.$modulo.'.key')]) {
                    return Http::response($registro);
                }
            }

            return Http::response(match ($path) {
                '/api/inventario/productos' => [[...$producto, 'marca' => 'Doite', 'categoria' => 'ALPINISMO', 'unidadesRegistradas' => 1, 'unidadesRequierenArreglo' => 1, 'unidadesConBaja' => 0]],
                '/api/inventario/productos/PRO-1' => $producto,
                '/api/registro-productos/catalogos' => ['clases' => [['codigo' => 'CLA-1', 'nombre' => 'Carpas']], 'marcas' => [], 'unidadesMedida' => [['codigo' => 'UM-1', 'nombre' => 'Unidad']]],
                '/api/inventario/productos/PRO-1/unidades' => ['producto' => $producto, 'almacenes' => [], 'unidades' => [
                    ['id' => 1, 'codigoUnico' => 'CD001', 'almacen' => 'Principal', 'estadoFisico' => 'REGULAR', 'calificacion' => null,
                        'requiereArreglo' => true, 'requiereCambio' => false, 'requiereBaja' => false, 'fechaAlta' => '2026-11-05', 'fechaBaja' => null, 'observaciones' => 'Revisar costura.'],
                ]],
                '/api/abc' => ['valorTotal' => 900, 'sinPrecio' => ['Equipo sin precio'], 'sinUnidades' => [], 'productos' => [
                    ['codigo' => 'PRO-1', 'nombre' => 'Carpa Doite', 'unidades' => 1, 'precioUnitario' => 900, 'valorTotal' => 900,
                        'porcentaje' => 100, 'porcentajeAcumulado' => 100, 'clasificacion' => 'A', 'fechaPrecio' => '2026-11-01'],
                ]],
                '/api/io/eoq' => ['loteTeorico' => 6.32, 'loteRecomendado' => 6, 'pedidosAnuales' => 2, 'diasEntrePedidos' => 180,
                    'demandaDiaria' => 0.0333, 'puntoReorden' => 0.5, 'costoCompraAnual' => 10800, 'costoOrdenarAnual' => 200,
                    'costoMantenerAnual' => 180, 'costoGestionAnual' => 380, 'costoTotalAnual' => 11180],
                default => throw new \RuntimeException('Solicitud inesperada: '.$path),
            });
        });
    }

    private function informe(string $html): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new DOMXPath($dom);
        $reports = $xpath->query('/html/body/article[@id="informe-impresion"]');
        $this->assertCount(1, $reports, 'Cada página debe tener un único informe, separado del panel.');
        $report = $reports->item(0);
        $this->assertInstanceOf(DOMElement::class, $report);
        $this->assertCount(0, $xpath->query('.//button|.//form|.//input|.//select|.//nav|.//aside|.//a', $report));
        $this->assertStringContainsString('THAKI VOYAGE', $report->textContent);
        $this->assertStringContainsString('Emitido el', $report->textContent);
        $this->assertStringContainsString('Fin del documento', $report->textContent);
        $this->assertStringNotContainsString('secreto-no-imprimible', $report->textContent);

        return [$xpath, $report];
    }

    public function test_todas_las_opciones_tienen_informe_separado_sin_controles(): void
    {
        $this->fixtures();
        foreach (['personas' => 'PER-1', 'empleados' => 'EMP-1', 'clientes' => 'CLI-1', 'usuarios' => 1, 'cargos' => 'CAR-1', 'departamentos' => 'DEP-1', 'roles' => 'ROL-1'] as $modulo => $codigo) {
            foreach (['/'.$modulo, '/'.$modulo.'/'.$codigo] as $path) {
                $response = $this->get($path)->assertOk()->assertSee('css/print.css', false);
                $this->informe($response->getContent());
            }
        }
        foreach (['/inventario', '/inventario/productos/PRO-1', '/inventario/PRO-1/unidades', '/inventario/abc'] as $path) {
            $this->informe($this->get($path)->assertOk()->getContent());
        }
    }

    public function test_ficha_de_empleado_formatea_fechas_importes_y_referencias(): void
    {
        $this->fixtures();
        [$xpath, $report] = $this->informe($this->get('/empleados/EMP-1')->assertOk()->getContent());
        foreach (['Ficha de empleado', 'EMP-1', 'Ana Pérez', 'Operaciones', '05/11/2026', '3.333,00 BOB', '05/11/2026 16:04'] as $text) {
            $this->assertStringContainsString($text, $report->textContent);
        }
        $this->assertStringNotContainsString('reporte-horizontal', $report->getAttribute('class'));
    }

    public function test_tablas_anchas_tienen_formato_horizontal_y_claves_para_respetar_filtros(): void
    {
        $this->fixtures();
        foreach (['/empleados', '/inventario', '/inventario/PRO-1/unidades', '/inventario/abc'] as $path) {
            [$xpath, $report] = $this->informe($this->get($path)->assertOk()->getContent());
            $this->assertStringContainsString('reporte-horizontal', $report->getAttribute('class'));
            $sourceKeys = [];
            foreach ($xpath->query('//*[@data-print-target]//tr[@data-print-key]') as $row) {
                $sourceKeys[] = $row->getAttribute('data-print-key');
            }
            $reportKeys = [];
            foreach ($xpath->query('.//tr[@data-print-key]', $report) as $row) {
                $reportKeys[] = $row->getAttribute('data-print-key');
            }
            $this->assertSame($sourceKeys, $reportKeys);
            $this->assertCount(1, $xpath->query('.//*[@data-report-count]', $report));
            $this->assertCount(1, $xpath->query('.//*[@data-report-filters]', $report));
        }
    }

    public function test_eoq_imprime_parametros_resultados_y_costos_en_bolivianos(): void
    {
        $this->fixtures();
        $response = $this->post('/investigacion-operativa/eoq', [
            'demandaAnual' => 12, 'costoPedido' => 100, 'costoMantenimientoAnual' => 60,
            'costoUnitario' => 900, 'plazoEntregaDias' => 15, 'diasAnuales' => 360,
        ])->assertOk();
        [$xpath, $report] = $this->informe($response->getContent());
        $this->assertStringContainsString('11.180,00 BOB', $report->textContent);
        $this->assertStringContainsString('Gestión (pedidos + mantenimiento)', $report->textContent);
        $this->assertStringNotContainsString('USD', $report->textContent);
        $this->assertCount(3, $xpath->query('.//table', $report));
    }

    public function test_confirmacion_de_eliminacion_no_publica_una_ficha_imprimible(): void
    {
        $this->fixtures();
        $this->get('/empleados/EMP-1/eliminar')->assertOk()->assertDontSee('id="informe-impresion"', false)->assertDontSee('data-print', false);
    }
}
