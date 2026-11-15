<?php

namespace App\Providers;

use App\Http\Middleware\RegistrarActividad;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\RequestInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::globalRequestMiddleware(function (RequestInterface $request): RequestInterface {
            $id = $this->solicitudQuarkus((string) $request->getUri());

            return $id === null ? $request : $request->withHeader(RegistrarActividad::HEADER, $id);
        });

        Event::listen(function (ConnectionFailed $event): void {
            $id = $this->solicitudQuarkus($event->request->url());
            if ($id !== null) {
                $this->registrarFalloQuarkus('quarkus.sin_conexion', $id, $event->request->method(), null);
            }
        });

        Event::listen(function (ResponseReceived $event): void {
            $id = $this->solicitudQuarkus($event->request->url());
            if ($id !== null && $event->response->serverError()) {
                $this->registrarFalloQuarkus('quarkus.error_http', $id, $event->request->method(), $event->response->status());
            }
        });
    }

    private function solicitudQuarkus(string $url): ?string
    {
        $base = rtrim((string) config('services.quarkus.url'), '/');
        if ($base === '' || ! str_starts_with($url, $base.'/api/')) {
            return null;
        }

        return request()->attributes->get('solicitud_id');
    }

    private function registrarFalloQuarkus(string $evento, string $id, string $metodo, ?int $estado): void
    {
        Log::channel('actividad')->error($evento, [
            'solicitud_id' => $id,
            'ruta' => request()->route()?->getName(),
            'metodo_api' => $metodo,
            'estado_api' => $estado,
        ]);
    }
}
