<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UnidadInventarioController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\ProductoController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});

foreach (['personas', 'empleados', 'clientes', 'usuarios', 'cargos', 'departamentos', 'roles'] as $modulo) {
    Route::get('/'.$modulo, [GestionController::class, 'index'])->defaults('modulo', $modulo)->name($modulo.'.index');
    Route::get('/'.$modulo.'/nuevo', [GestionController::class, 'create'])->defaults('modulo', $modulo)->name($modulo.'.create');
    Route::post('/'.$modulo, [GestionController::class, 'store'])->defaults('modulo', $modulo)->name($modulo.'.store');
    Route::get('/'.$modulo.'/{codigo}/editar', [GestionController::class, 'edit'])->defaults('modulo', $modulo)->name($modulo.'.edit');
    Route::get('/'.$modulo.'/{codigo}/eliminar', [GestionController::class, 'delete'])->defaults('modulo', $modulo)->name($modulo.'.delete');
    Route::get('/'.$modulo.'/{codigo}', [GestionController::class, 'show'])->defaults('modulo', $modulo)->name($modulo.'.show');
    Route::put('/'.$modulo.'/{codigo}', [GestionController::class, 'update'])->defaults('modulo', $modulo)->name($modulo.'.update');
    Route::delete('/'.$modulo.'/{codigo}', [GestionController::class, 'destroy'])->defaults('modulo', $modulo)->name($modulo.'.destroy');
}


Route::get('/investigacion-operativa', function () {
    return view('io.index');
})->name('io.index');

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

Route::get('/inventario/productos/{codigo}/editar', [ProductoController::class, 'edit'])->name('inventario.editar');
Route::get('/inventario/productos/{codigo}', [ProductoController::class, 'show'])->where('codigo', '(?!nuevo$)[^/]+')->name('inventario.producto');
Route::put('/inventario/productos/{codigo}', [ProductoController::class, 'update'])->name('inventario.actualizar');

Route::get('/inventario/{codigo}/unidades', [UnidadInventarioController::class, 'index'])
    ->name('inventario.unidades');

Route::post('/inventario/{codigo}/unidades', [UnidadInventarioController::class, 'store'])
    ->name('inventario.unidades.guardar');

Route::get('/inventario/{codigo}/unidades/{id}/editar', [UnidadInventarioController::class, 'edit'])->whereNumber('id')->name('inventario.unidades.editar');
Route::put('/inventario/{codigo}/unidades/{id}', [UnidadInventarioController::class, 'update'])->whereNumber('id')->name('inventario.unidades.actualizar');

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
