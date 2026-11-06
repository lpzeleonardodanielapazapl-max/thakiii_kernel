<?php

use App\Http\Controllers\AccesoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AtencionController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PublicoController;
use App\Http\Controllers\UnidadInventarioController;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicoController::class, 'inicio'])->name('publico.inicio');
Route::get('/planificar-viaje', [PublicoController::class, 'planificar'])->name('publico.planificar');
Route::get('/inspiracion/{slug}', [PublicoController::class, 'inspiracion'])->name('publico.inspiracion');
Route::get('/destinos/{codigo}', [PublicoController::class, 'destino'])->name('publico.destino');
Route::get('/ingresar', [AccesoController::class, 'ingresar'])->name('acceso.ingresar');
Route::post('/ingresar', [AccesoController::class, 'autenticar'])->middleware('throttle:8,1')->name('acceso.autenticar');
Route::get('/registro', [AccesoController::class, 'registro'])->name('acceso.registro');
Route::post('/registro', [AccesoController::class, 'registrar'])->middleware('throttle:5,1')->name('acceso.registrar');
Route::get('/configurar-administracion', [AccesoController::class, 'configurar'])->name('acceso.configurar');
Route::post('/configurar-administracion', [AccesoController::class, 'inicializar'])->middleware('throttle:5,1')->name('acceso.inicializar');
Route::post('/salir', [AccesoController::class, 'salir'])->name('acceso.salir');

Route::middleware('sesion:cliente')->prefix('mi-viaje')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'inicio'])->name('inicio');
    Route::get('/perfil', [PortalController::class, 'perfil'])->name('perfil');
    Route::put('/perfil', [PortalController::class, 'guardarPerfil'])->name('perfil.guardar');
    Route::get('/solicitar', [PortalController::class, 'solicitud'])->name('solicitud');
    Route::post('/solicitudes', [PortalController::class, 'solicitar'])->name('solicitar');
    Route::get('/mensajes', [PortalController::class, 'mensajes'])->name('mensajes');
    Route::post('/mensajes', [PortalController::class, 'enviar'])->name('mensajes.enviar');
    Route::post('/asignacion', [PortalController::class, 'asignar'])->name('asignar');
    Route::get('/notificaciones', [PortalController::class, 'notificaciones'])->name('notificaciones');
    Route::post('/notificaciones/{id}/leer', [PortalController::class, 'leer'])->whereNumber('id')->name('notificaciones.leer');
    Route::get('/propuestas/{codigo}', [PortalController::class, 'propuesta'])->name('propuesta');
    Route::post('/propuestas/{codigo}/aceptar', [PortalController::class, 'aceptar'])->name('propuesta.aceptar');
    Route::post('/reservas/{codigo}/cancelacion', [PortalController::class, 'cancelar'])->name('cancelacion');
});

Route::middleware('sesion:personal')->group(function () {

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
            $respuesta = Http::baseUrl(config('services.quarkus.url'))->withToken((string) session('api_token', ''))
                ->acceptJson()
                ->timeout(15)
                ->post('/api/io/eoq', $datos);

            $respuesta->throw();

            return view('io.index', [
                'resultado' => $respuesta->json(),
                'datos' => $datos,
            ]);
        } catch (ConnectionException $e) {
            return back()->withInput()->withErrors([
                'api' => 'No se pudo conectar con Quarkus. Comprueba que esté iniciado.',
            ]);
        } catch (RequestException $e) {
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
    Route::view('/admin/gestion', 'admin.gestion')->name('admin.gestion');

    Route::get(
        '/inventario/productos/nuevo',
        [InventarioController::class, 'crearProducto']
    )->name('inventario.crear');

    Route::post(
        '/inventario/productos',
        [InventarioController::class, 'guardarProducto']
    )->name('inventario.guardar');

    Route::get('/atencion', [AtencionController::class, 'index'])->name('atencion.index');
    Route::get('/atencion/calendario', [AtencionController::class, 'calendario'])->name('atencion.calendario');
    Route::get('/atencion/conversaciones/{id}', [AtencionController::class, 'conversacion'])->whereNumber('id')->name('atencion.conversacion');
    Route::post('/atencion/conversaciones/{id}/mensajes', [AtencionController::class, 'responder'])->whereNumber('id')->name('atencion.responder');
    Route::get('/atencion/solicitudes/{codigo}', [AtencionController::class, 'solicitud'])->name('atencion.solicitud');
    Route::post('/atencion/solicitudes/{codigo}/propuestas', [AtencionController::class, 'proponer'])->name('atencion.proponer');
    Route::post('/atencion/propuestas/{codigo}/aceptacion', [AtencionController::class, 'aceptar'])->name('atencion.aceptar');
    Route::get('/atencion/reservas/{codigo}', [AtencionController::class, 'reserva'])->name('atencion.reserva');
    Route::post('/atencion/reservas/{codigo}/pagos', [AtencionController::class, 'pago'])->name('atencion.pago');
    Route::post('/atencion/reservas/{codigo}/cancelacion', [AtencionController::class, 'cancelar'])->name('atencion.cancelar');
});
