@extends('layouts.admin')
@section('titulo', ($confirmDelete ? 'Eliminar ' : 'Detalle de ').$meta['singular'])
@section('contenido')
    <x-page-header :title="($confirmDelete ? 'Eliminar ' : 'Detalle de ').$meta['singular']" :description="'Código: '.$registro[$meta['key']]" eyebrow="GESTIÓN · FICHA DEL REGISTRO">
        <a class="boton boton-secundario" href="{{ route($meta['modulo'].'.index') }}"><x-icon name="back"/> {{ $meta['title'] }}</a>
        @if (! $confirmDelete)<a class="boton" href="{{ route($meta['modulo'].'.edit', ['codigo' => $registro[$meta['key']]]) }}">Editar datos</a>@endif
    </x-page-header>
    @if (session('exito'))<div class="aviso" role="status">{{ session('exito') }}</div>@endif
    @if ($errors->any())<div class="aviso error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="panel">
        <h2>Datos y detalles</h2>
        <dl class="ficha-grid">
            @foreach ($meta['fields'] as $nombre => $campo)
                @continue($campo['type'] === 'password')
                @php($valor = $registro[$nombre] ?? null)
                <div><dt>{{ $campo['label'] }}</dt><dd>
                    @if ($campo['type'] === 'checkbox')<span @class(['badge', 'badge-verde' => $valor, 'badge-gris' => ! $valor])>{{ $valor ? 'Sí' : 'No' }}</span>
                    @elseif (isset($campo['catalogo']))
                        @if ($valor)<a class="enlace-registro" href="{{ route($campo['catalogo'].'.show', ['codigo' => $valor]) }}">{{ $catalogos[$campo['catalogo']][$valor] ?? $valor }} ↗</a>@else — @endif
                    @elseif (isset($campo['options'])) {{ $campo['options'][$valor] ?? $valor ?? '—' }}
                    @else {{ $valor ?? '—' }} @endif
                </dd></div>
            @endforeach
            @foreach (['createdAt' => 'Registrado el', 'updatedAt' => 'Última actualización'] as $nombre => $etiqueta)
                @if ($registro[$nombre] ?? null)<div><dt>{{ $etiqueta }}</dt><dd>{{ str_replace('T', ' ', $registro[$nombre]) }}</dd></div>@endif
            @endforeach
        </dl>
    </section>
    @if ($confirmDelete)
        <section class="panel panel-peligro"><h2>Confirma la eliminación</h2><p>Esta acción elimina permanentemente el registro. Si tiene información vinculada, el sistema impedirá su eliminación para conservar el historial.</p>
            @if (isset($meta['fields']['estado']) || isset($meta['fields']['activo']))<p>Puedes <a href="{{ route($meta['modulo'].'.edit', ['codigo' => $registro[$meta['key']]]) }}">editar el registro y desactivarlo</a> si necesitas conservar sus datos.</p>@endif
            <form method="POST" action="{{ route($meta['modulo'].'.destroy', ['codigo' => $registro[$meta['key']]]) }}">@csrf @method('DELETE')
                <label class="check-tarjeta"><input type="checkbox" name="confirmar" value="1" required> Confirmo que quiero eliminar este registro.</label>
                <div class="formulario-acciones"><button class="boton" type="submit">Eliminar definitivamente</button><a class="boton boton-secundario" href="{{ route($meta['modulo'].'.show', ['codigo' => $registro[$meta['key']]]) }}">Conservar registro</a></div>
            </form>
        </section>
    @else
        <div class="acciones-pagina"><button type="button" class="boton boton-secundario" data-print><x-icon name="print"/> Imprimir ficha</button>
            @if ($meta['canDelete'] ?? true)<a class="accion-enlace" href="{{ route($meta['modulo'].'.delete', ['codigo' => $registro[$meta['key']]]) }}">Eliminar registro</a>@endif
        </div>
    @endif
@endsection
