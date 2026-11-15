<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RegistrarActividad
{
    public const HEADER = 'X-Request-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) Str::uuid();
        $request->attributes->set('solicitud_id', $id);
        $inicio = hrtime(true);

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        $estado = $response->getStatusCode();
        $ruta = $request->route()?->getName();
        $esCambio = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        if ($estado >= 500 || ($ruta !== null && ($esCambio || $estado >= 400))) {
            $rechazado = $estado >= 400 || ($esCambio && $response->isRedirection()
                && $request->hasSession() && $request->session()->has('errors'));

            Log::channel('actividad')->log(
                $estado >= 500 ? 'error' : ($rechazado ? 'warning' : 'info'),
                'web.respuesta',
                [
                    'solicitud_id' => $id,
                    'ruta' => $ruta,
                    'metodo' => $request->method(),
                    'estado_http' => $estado,
                    'resultado' => $estado >= 500 ? 'error' : ($rechazado ? 'rechazado' : 'respondido'),
                    'usuario_id' => $request->hasSession() ? $request->session()->get('usuario.id') : null,
                    'duracion_ms' => (int) ((hrtime(true) - $inicio) / 1_000_000),
                ],
            );
        }

        return $response;
    }
}
