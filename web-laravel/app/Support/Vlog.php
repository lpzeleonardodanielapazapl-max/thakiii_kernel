<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Vlog
{
    public static function archivo(): string
    {
        return storage_path('app/vlog/posts.json');
    }

    public static function carpeta(): string
    {
        return storage_path('app/vlog');
    }

    /** @return list<array<string, mixed>> */
    public static function todos(): array
    {
        $archivo = self::archivo();
        if (! is_file($archivo)) {
            return [];
        }

        $datos = json_decode((string) file_get_contents($archivo), true);
        if (! is_array($datos)) {
            return [];
        }

        return array_map([self::class, 'normalizar'], array_values($datos));
    }

    /** @return array<string, mixed>|null */
    public static function buscar(string $id): ?array
    {
        foreach (self::todos() as $post) {
            if (($post['id'] ?? '') === $id) {
                return $post;
            }
        }

        return null;
    }

    /**
     * @param  array{nombre:string,comentario:string,destino?:string|null,usuarioId?:string|null,usuarioRol?:string|null}  $entrada
     * @param  list<UploadedFile>  $imagenes
     * @return array<string, mixed>
     */
    public static function publicar(array $entrada, array $imagenes): array
    {
        $id = (string) Str::uuid();
        $carpeta = self::carpeta();
        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        $nombres = [];
        foreach (array_values($imagenes) as $indice => $imagen) {
            if (! $imagen instanceof UploadedFile) {
                continue;
            }
            $extension = strtolower($imagen->getClientOriginalExtension() ?: $imagen->extension() ?: 'jpg');
            $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
            $nombreArchivo = $id.($indice === 0 ? '' : '-'.$indice).'.'.$extension;
            $imagen->move($carpeta, $nombreArchivo);
            $nombres[] = $nombreArchivo;
        }

        abort_if($nombres === [], 422, 'Debes subir al menos una foto.');

        $post = self::normalizar([
            'id' => $id,
            'nombre' => trim($entrada['nombre']),
            'comentario' => trim($entrada['comentario']),
            'destino' => trim((string) ($entrada['destino'] ?? '')) ?: null,
            'imagen' => $nombres[0],
            'imagenes' => $nombres,
            'usuarioId' => $entrada['usuarioId'] ?? null,
            'usuarioRol' => $entrada['usuarioRol'] ?? null,
            'creado' => now()->toIso8601String(),
            'likes' => [],
            'comentarios' => [],
        ]);

        $posts = self::todos();
        array_unshift($posts, $post);
        self::guardar($posts);

        return $post;
    }

    /**
     * @param  array{id:string,nombre:string}  $usuario
     * @return array<string, mixed>|null
     */
    public static function alternarMeGusta(string $postId, array $usuario): ?array
    {
        $posts = self::todos();
        foreach ($posts as $i => $post) {
            if (($post['id'] ?? '') !== $postId) {
                continue;
            }

            $likes = $post['likes'] ?? [];
            $existe = false;
            foreach ($likes as $j => $like) {
                if (($like['usuarioId'] ?? '') === $usuario['id']) {
                    unset($likes[$j]);
                    $existe = true;
                    break;
                }
            }

            if (! $existe) {
                $likes[] = [
                    'usuarioId' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'creado' => now()->toIso8601String(),
                ];
            }

            $posts[$i]['likes'] = array_values($likes);
            self::guardar($posts);

            return self::normalizar($posts[$i]);
        }

        return null;
    }

    /**
     * @param  array{id:string,nombre:string}  $usuario
     * @return array<string, mixed>|null
     */
    public static function comentar(string $postId, array $usuario, string $texto): ?array
    {
        $posts = self::todos();
        foreach ($posts as $i => $post) {
            if (($post['id'] ?? '') !== $postId) {
                continue;
            }

            $comentarios = $post['comentarios'] ?? [];
            $comentarios[] = [
                'id' => (string) Str::uuid(),
                'usuarioId' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'texto' => trim($texto),
                'creado' => now()->toIso8601String(),
            ];
            $posts[$i]['comentarios'] = $comentarios;
            self::guardar($posts);

            return self::normalizar($posts[$i]);
        }

        return null;
    }

    public static function rutaImagen(string $id, int $indice = 0): ?string
    {
        $post = self::buscar($id);
        if (! $post) {
            return null;
        }

        $imagenes = $post['imagenes'] ?? [];
        $archivo = $imagenes[$indice] ?? $imagenes[0] ?? ($post['imagen'] ?? null);
        if (! is_string($archivo) || $archivo === '') {
            return null;
        }

        $ruta = self::carpeta().DIRECTORY_SEPARATOR.$archivo;

        return is_file($ruta) ? $ruta : null;
    }

    /** @return list<string> */
    public static function urlsImagenes(array $post): array
    {
        $id = (string) ($post['id'] ?? '');
        $imagenes = $post['imagenes'] ?? [];
        if ($imagenes === [] && ! empty($post['imagen'])) {
            $imagenes = [$post['imagen']];
        }

        $urls = [];
        foreach (array_values($imagenes) as $indice => $archivo) {
            $urls[] = route('publico.vlog.imagen', ['id' => $id, 'indice' => $indice]);
        }

        return $urls;
    }

    public static function usuarioDioLike(array $post, ?string $usuarioId): bool
    {
        if (! $usuarioId) {
            return false;
        }

        foreach ($post['likes'] ?? [] as $like) {
            if (($like['usuarioId'] ?? '') === $usuarioId) {
                return true;
            }
        }

        return false;
    }

    public static function eliminarPublicacion(string $id): bool
    {
        $posts = self::todos();
        $quedan = [];
        $eliminado = null;

        foreach ($posts as $post) {
            if (($post['id'] ?? '') === $id) {
                $eliminado = $post;
                continue;
            }
            $quedan[] = $post;
        }

        if ($eliminado === null) {
            return false;
        }

        self::guardar($quedan);

        $imagenes = $eliminado['imagenes'] ?? [];
        if ($imagenes === [] && ! empty($eliminado['imagen'])) {
            $imagenes = [$eliminado['imagen']];
        }
        foreach ($imagenes as $archivo) {
            $ruta = self::carpeta().DIRECTORY_SEPARATOR.$archivo;
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }

        return true;
    }

    public static function eliminarComentario(string $postId, string $comentarioId): bool
    {
        $posts = self::todos();
        foreach ($posts as $i => $post) {
            if (($post['id'] ?? '') !== $postId) {
                continue;
            }

            $antes = count($post['comentarios'] ?? []);
            $posts[$i]['comentarios'] = array_values(array_filter(
                $post['comentarios'] ?? [],
                fn (array $comentario): bool => ($comentario['id'] ?? '') !== $comentarioId
            ));

            if (count($posts[$i]['comentarios']) === $antes) {
                return false;
            }

            self::guardar($posts);

            return true;
        }

        return false;
    }

    /** @param  array<string, mixed>  $post */
    private static function normalizar(array $post): array
    {
        $post['likes'] = array_values(is_array($post['likes'] ?? null) ? $post['likes'] : []);
        $post['comentarios'] = array_values(is_array($post['comentarios'] ?? null) ? $post['comentarios'] : []);

        $imagenes = [];
        if (is_array($post['imagenes'] ?? null)) {
            foreach ($post['imagenes'] as $archivo) {
                if (is_string($archivo) && $archivo !== '') {
                    $imagenes[] = $archivo;
                }
            }
        }
        if ($imagenes === [] && ! empty($post['imagen']) && is_string($post['imagen'])) {
            $imagenes[] = $post['imagen'];
        }

        $post['imagenes'] = array_values($imagenes);
        $post['imagen'] = $post['imagenes'][0] ?? ($post['imagen'] ?? null);

        return $post;
    }

    /** @param  list<array<string, mixed>>  $posts */
    private static function guardar(array $posts): void
    {
        $archivo = self::archivo();
        $dir = dirname($archivo);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $archivo,
            json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n",
            LOCK_EX
        );
    }
}
