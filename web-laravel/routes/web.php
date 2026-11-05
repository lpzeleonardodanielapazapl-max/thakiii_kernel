<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/roles', [RolController::class, 'index']);
Route::get('/personas', [PersonaController::class, 'index']);
Route::get('/empleados', [EmpleadoController::class, 'index']);
Route::get('/clientes', [ClienteController::class, 'index']);
Route::get('/usuarios', [UsuarioController::class, 'index']);


Route::get('/investigacion-operativa', function () {
    return view('io.index');
});

Route::post('/investigacion-operativa/eoq', function (Request $request) {
    $datos = $request->validate([
        'demandaAnual' => ['required', 'numeric', 'gt:0'],
        'costoPedido' => ['required', 'numeric', 'gt:0'],
        'costoMantenimientoAnual' => ['required', 'numeric', 'gt:0'],
        'costoUnitario' => ['required', 'numeric', 'min:0'],
        'plazoEntregaDias' => ['required', 'numeric', 'min:0'],
        'diasAnuales' => ['required', 'numeric', 'gt:0'],
    ]);

    $datos = array_map(fn ($valor) => (float) $valor, $datos);

    try {
        $respuesta = Http::baseUrl(config('services.quarkus.url'))
            ->acceptJson()
            ->timeout(15)
            ->post('/api/io/eoq', $datos);

        $respuesta->throw();

        return view('io.index', [
            'resultado' => $respuesta->json(),
            'datos' => $datos,
        ]);
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        return back()->withInput()->withErrors([
            'api' => 'No se pudo conectar con Quarkus. Comprueba que esté iniciado.',
        ]);
    } catch (\Illuminate\Http\Client\RequestException $e) {
        return back()->withInput()->withErrors([
            'api' => 'Quarkus rechazó el cálculo. Revisa los parámetros y su consola.',
        ]);
    }
});


Route::get('/inventario', [InventarioController::class, 'index'])
    ->name('inventario.index');

Route::get(
    '/inventario/{codigo}/reposicion',
    [InventarioController::class, 'analizar']
)->name('inventario.analizar');

Route::post(
    '/inventario/{codigo}/reposicion',
    [InventarioController::class, 'calcular']
)->name('inventario.calcular');

Route::get(
    '/inventario/abc',
    [InventarioController::class, 'abc']
)->name('inventario.abc');

Route::get('/admin', [AdminController::class, 'index'])
    ->name('admin.index');

Route::get(
    '/inventario/productos/nuevo',
    [InventarioController::class, 'crearProducto']
)->name('inventario.crear');

Route::post(
    '/inventario/productos',
    [InventarioController::class, 'guardarProducto']
)->name('inventario.guardar');