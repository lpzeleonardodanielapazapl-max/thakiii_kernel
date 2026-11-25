@extends('layouts.admin')
@section('titulo', 'Moderación del vlog')
@section('contenido')
<x-page-header title="Moderación del vlog" description="Revisa, limpia comentarios y elimina publicaciones que no correspondan a la comunidad Thaki." eyebrow="PORTADA PÚBLICA">
    <a class="boton boton-secundario" href="{{ route('publico.vlog') }}" target="_blank" rel="noopener">Ver vlog público ↗</a>
</x-page-header>

@if(session('estado'))
    <div class="aviso" role="status">{{ session('estado') }}</div>
@endif

<section class="moderacion-resumen" aria-label="Resumen del vlog">
    <article><span>Publicaciones</span><strong>{{ number_format($total, 0, ',', '.') }}</strong></article>
    <article><span>Comentarios</span><strong>{{ number_format($comentarios, 0, ',', '.') }}</strong></article>
    <article><span>Rol</span><strong>Solo administración</strong></article>
</section>

<div class="moderacion-lista" aria-label="Publicaciones del vlog">
    @forelse($publicaciones as $post)
        @php
            $likes = $post['likes'] ?? [];
            $respuestas = $post['comentarios'] ?? [];
        @endphp
        @php($fotos = \App\Support\Vlog::urlsImagenes($post))
        <article class="moderacion-tarjeta" id="vlog-{{ $post['id'] }}">
            <div class="moderacion-fotos">
                <img src="{{ $fotos[0] ?? route('publico.vlog.imagen', $post['id']) }}" alt="Publicación de {{ $post['nombre'] }}" loading="lazy">
                @if(count($fotos) > 1)
                    <span class="vlog-foto-badge">{{ count($fotos) }} fotos</span>
                @endif
            </div>
            <div class="moderacion-cuerpo">
                <header>
                    <div>
                        @if(!empty($post['destino']))
                            <span class="etiqueta">{{ $post['destino'] }}</span>
                        @endif
                        <h2>{{ $post['nombre'] }}</h2>
                        <time datetime="{{ $post['creado'] }}">{{ \Illuminate\Support\Carbon::parse($post['creado'])->timezone('America/La_Paz')->format('d/m/Y H:i') }}</time>
                    </div>
                    <form method="POST" action="{{ route('admin.vlog.eliminar', $post['id']) }}" onsubmit="return confirm('¿Eliminar esta publicación y todos sus comentarios?');">
                        @csrf
                        <button class="boton boton-secundario boton-pequeno" type="submit">Eliminar publicación</button>
                    </form>
                </header>
                <p class="moderacion-texto">“{{ $post['comentario'] }}”</p>
                <p class="moderacion-meta">{{ count($likes) }} me gusta · {{ count($respuestas) }} comentario{{ count($respuestas) === 1 ? '' : 's' }}</p>

                @if(count($respuestas))
                    <ul class="moderacion-comentarios">
                        @foreach($respuestas as $respuesta)
                            <li>
                                <div>
                                    <strong>{{ $respuesta['nombre'] }}</strong>
                                    <span>{{ $respuesta['texto'] }}</span>
                                    <time datetime="{{ $respuesta['creado'] }}">{{ \Illuminate\Support\Carbon::parse($respuesta['creado'])->timezone('America/La_Paz')->format('d/m/Y H:i') }}</time>
                                </div>
                                <form method="POST" action="{{ route('admin.vlog.comentario.eliminar', ['id' => $post['id'], 'comentario' => $respuesta['id']]) }}" onsubmit="return confirm('¿Eliminar este comentario?');">
                                    @csrf
                                    <button class="accion-enlace" type="submit">Eliminar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="moderacion-vacio">Sin comentarios en esta publicación.</p>
                @endif
            </div>
        </article>
    @empty
        <div class="estado-vacio moderacion-sin-datos">
            <x-icon name="chat"/>
            <strong>Aún no hay publicaciones</strong>
            <span>Cuando los clientes compartan fotos en el vlog, podrás moderarlas aquí.</span>
            <a class="boton" href="{{ route('publico.vlog') }}">Abrir vlog público</a>
        </div>
    @endforelse
</div>
@endsection
