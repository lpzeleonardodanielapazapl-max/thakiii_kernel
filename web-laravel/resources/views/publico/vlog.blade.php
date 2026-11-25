@extends('layouts.publico')
@section('titulo', 'Vlog de viajeros')
@section('publico-clase', 'pagina-vlog')
@section('contenido')
<section class="vlog-hero">
    <div>
        <span class="etiqueta clara">EXPERIENCIAS REALES</span>
        <h1>Vlog de <em>viajeros.</em></h1>
        <p>Fotos y comentarios de quienes ya recorrieron Bolivia con Thaki. Inspírate con sus historias; si ya viajaste, comparte la tuya.</p>
    </div>
</section>

<section class="seccion vlog-cuerpo">
    @if(session('estado'))
        <div class="aviso" role="status">{{ session('estado') }}</div>
    @endif
    @if(session('aviso'))
        <div class="aviso" role="status">{{ session('aviso') }}</div>
    @endif

    <div class="vlog-panel">
        @if($puedePublicar)
            <form class="vlog-formulario" method="POST" action="{{ route('publico.vlog.publicar') }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <span class="etiqueta">COMPARTE TU VIAJE</span>
                    <h2>Publica tus fotos y tu experiencia</h2>
                    <p>Puedes subir una o varias fotos (hasta 6). Tu nombre aparecerá junto a la publicación.</p>
                </div>
                <label for="vlog-destino">Destino o ruta (opcional)
                    <input id="vlog-destino" name="destino" maxlength="120" value="{{ old('destino') }}" placeholder="Uyuni, Titicaca, Madidi…">
                </label>
                <label for="vlog-comentario">Tu comentario
                    <textarea id="vlog-comentario" name="comentario" rows="4" maxlength="800" required placeholder="¿Qué fue lo que más te marcó del viaje?">{{ old('comentario') }}</textarea>
                </label>
                <label class="vlog-archivo" for="vlog-imagen">
                    <span>Fotos del viaje</span>
                    <input id="vlog-imagen" type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple required>
                    <small>JPG, PNG o WEBP · hasta 6 fotos · 4 MB cada una</small>
                </label>
                @if($errors->any() && !old('texto'))
                    <div class="aviso error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <button class="boton" type="submit">Publicar en el vlog ↗</button>
            </form>
        @else
            <aside class="vlog-invitar">
                <span class="etiqueta">¿YA VIAJASTE CON NOSOTROS?</span>
                <h2>Ingresa para participar</h2>
                <p>Cualquiera puede ver las fotos. Para subir, dar me gusta o comentar necesitas iniciar sesión.</p>
                <div class="vlog-invitar-acciones">
                    <a class="boton" href="{{ route('acceso.ingresar') }}">Ingresar ↗</a>
                    <a class="enlace" href="{{ route('acceso.registro') }}">Crear cuenta</a>
                </div>
            </aside>
        @endif

        <div class="vlog-listado" aria-label="Publicaciones del vlog">
            @forelse($publicaciones as $post)
                @php
                    $likes = $post['likes'] ?? [];
                    $respuestas = $post['comentarios'] ?? [];
                    $dioLike = \App\Support\Vlog::usuarioDioLike($post, $usuarioId);
                    $fotos = \App\Support\Vlog::urlsImagenes($post);
                @endphp
                <article class="vlog-tarjeta" id="vlog-{{ $post['id'] }}">
                    <button type="button" class="vlog-foto-abrir" data-vlog-abrir data-vlog-fotos='@json($fotos)' data-vlog-inicio="0">
                        <img src="{{ $fotos[0] ?? route('publico.vlog.imagen', $post['id']) }}" alt="Experiencia de {{ $post['nombre'] }}" loading="lazy">
                        @if(count($fotos) > 1)
                            <span class="vlog-foto-badge">{{ count($fotos) }} fotos</span>
                        @endif
                    </button>
                    <div>
                        @if(!empty($post['destino']))
                            <span class="etiqueta">{{ $post['destino'] }}</span>
                        @endif
                        <p>“{{ $post['comentario'] }}”</p>
                        <footer>
                            <strong>{{ $post['nombre'] }}</strong>
                            <time datetime="{{ $post['creado'] }}">{{ \Illuminate\Support\Carbon::parse($post['creado'])->timezone(config('app.timezone'))->format('d/m/Y') }}</time>
                        </footer>

                        @if(count($fotos) > 1)
                            <div class="vlog-miniaturas">
                                @foreach($fotos as $i => $foto)
                                    <button type="button" data-vlog-abrir data-vlog-fotos='@json($fotos)' data-vlog-inicio="{{ $i }}">
                                        <img src="{{ $foto }}" alt="Foto {{ $i + 1 }}" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="vlog-interacciones">
                            <div class="vlog-reacciones">
                                @if($puedePublicar)
                                    <form method="POST" action="{{ route('publico.vlog.megusta', $post['id']) }}">
                                        @csrf
                                        <button class="vlog-like {{ $dioLike ? 'activo' : '' }}" type="submit" aria-pressed="{{ $dioLike ? 'true' : 'false' }}">
                                            {{ $dioLike ? '♥ Te gusta' : '♡ Me gusta' }}
                                            <span>{{ count($likes) }}</span>
                                        </button>
                                    </form>
                                @else
                                    <a class="vlog-like" href="{{ route('acceso.ingresar') }}">♡ Me gusta <span>{{ count($likes) }}</span></a>
                                @endif
                                <span class="vlog-conteo">{{ count($respuestas) }} comentario{{ count($respuestas) === 1 ? '' : 's' }}</span>
                            </div>

                            @if(count($respuestas))
                                <ul class="vlog-comentarios">
                                    @foreach($respuestas as $respuesta)
                                        <li>
                                            <strong>{{ $respuesta['nombre'] }}</strong>
                                            <span>{{ $respuesta['texto'] }}</span>
                                            <time datetime="{{ $respuesta['creado'] }}">{{ \Illuminate\Support\Carbon::parse($respuesta['creado'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if($puedePublicar)
                                <form class="vlog-comentar" method="POST" action="{{ route('publico.vlog.comentar', $post['id']) }}">
                                    @csrf
                                    <label class="solo-lectores" for="comentario-{{ $post['id'] }}">Escribe un comentario</label>
                                    <input id="comentario-{{ $post['id'] }}" name="texto" maxlength="400" required placeholder="Escribe un comentario…">
                                    <button class="boton boton-pequeno" type="submit">Comentar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="vlog-vacio">
                    <h3>Aún no hay publicaciones</h3>
                    <p>Cuando los viajeros compartan sus fotos, aparecerán aquí para que todos puedan verlas.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
<x-vlog-lightbox/>
@endsection
