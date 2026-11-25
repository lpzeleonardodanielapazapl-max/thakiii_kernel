<?php

namespace App\Http\Controllers;

use App\Support\Vlog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VlogController extends Controller
{
    public function index(): View
    {
        $usuario = session('usuario');
        $usuarioId = $this->idUsuario($usuario);

        return view('publico.vlog', [
            'publicaciones' => Vlog::todos(),
            'puedePublicar' => (bool) $usuario,
            'usuarioId' => $usuarioId,
        ]);
    }

    public function publicar(Request $request): RedirectResponse
    {
        if (! session('usuario')) {
            return redirect()
                ->route('acceso.ingresar')
                ->with('aviso', 'Ingresa para compartir tu experiencia en el vlog.');
        }

        $datos = $request->validate([
            'comentario' => ['required', 'string', 'min:10', 'max:800'],
            'destino' => ['nullable', 'string', 'max:120'],
            'imagenes' => ['required', 'array', 'min:1', 'max:6'],
            'imagenes.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'comentario.required' => 'Cuéntanos un poco de tu viaje.',
            'comentario.min' => 'El comentario necesita al menos 10 caracteres.',
            'imagenes.required' => 'Sube al menos una foto de tu experiencia.',
            'imagenes.min' => 'Sube al menos una foto de tu experiencia.',
            'imagenes.max' => 'Puedes subir hasta 6 fotos por publicación.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.max' => 'Cada imagen no puede superar 4 MB.',
        ]);

        $actor = $this->actor();

        Vlog::publicar([
            'nombre' => $actor['nombre'],
            'comentario' => $datos['comentario'],
            'destino' => $datos['destino'] ?? null,
            'usuarioId' => $actor['id'],
            'usuarioRol' => (string) (session('usuario.rol') ?? ''),
        ], $request->file('imagenes', []));

        return redirect()
            ->route('publico.vlog')
            ->with('estado', 'Tu experiencia ya está en el vlog. ¡Gracias por compartirla!');
    }

    public function meGusta(string $id): RedirectResponse
    {
        if (! session('usuario')) {
            return redirect()
                ->route('acceso.ingresar')
                ->with('aviso', 'Ingresa para dar me gusta a una foto.');
        }

        abort_unless(Vlog::buscar($id), 404);
        Vlog::alternarMeGusta($id, $this->actor());

        return redirect()
            ->to(route('publico.vlog').'#vlog-'.$id)
            ->with('estado', 'Tu reacción se guardó.');
    }

    public function comentar(Request $request, string $id): RedirectResponse
    {
        if (! session('usuario')) {
            return redirect()
                ->route('acceso.ingresar')
                ->with('aviso', 'Ingresa para comentar una foto.');
        }

        abort_unless(Vlog::buscar($id), 404);

        $datos = $request->validate([
            'texto' => ['required', 'string', 'min:2', 'max:400'],
        ], [
            'texto.required' => 'Escribe un comentario.',
            'texto.min' => 'El comentario es muy corto.',
        ]);

        Vlog::comentar($id, $this->actor(), $datos['texto']);

        return redirect()
            ->to(route('publico.vlog').'#vlog-'.$id)
            ->with('estado', 'Tu comentario se publicó.');
    }

    public function imagen(string $id, int $indice = 0): BinaryFileResponse|Response
    {
        $ruta = Vlog::rutaImagen($id, max(0, $indice));
        abort_unless($ruta, 404);

        return response()->file($ruta, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /** @return array{id:string,nombre:string} */
    private function actor(): array
    {
        $usuario = session('usuario');
        $nombre = trim((string) ($usuario['nombre'] ?? $usuario['nombres'] ?? 'Viajero Thaki'));

        return [
            'id' => $this->idUsuario($usuario) ?: 'anonimo',
            'nombre' => $nombre !== '' ? $nombre : 'Viajero Thaki',
        ];
    }

    private function idUsuario(mixed $usuario): ?string
    {
        if (! is_array($usuario)) {
            return null;
        }

        foreach (['codigo', 'id', 'idUsuario', 'email', 'username'] as $campo) {
            $valor = trim((string) ($usuario[$campo] ?? ''));
            if ($valor !== '') {
                return $valor;
            }
        }

        return null;
    }
}
