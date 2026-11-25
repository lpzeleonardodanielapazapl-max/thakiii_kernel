@props(['posts' => [], 'empresa' => []])
@php
    $posts = array_values($posts);
    $total = count($posts);
@endphp
<section class="vlog-showcase-seccion seccion" id="experiencias" aria-label="Experiencias de viajeros">
    <div class="vlog-showcase-encabezado">
        <div>
            <span class="etiqueta">EXPERIENCIAS REALES</span>
            <h2>Vlogs de <em>viajeros.</em></h2>
        </div>
        <a class="enlace" href="{{ route('publico.vlog') }}">Ver todo el vlog ↗</a>
    </div>

    @if($total)
        <div class="vlog-showcase" data-vlog-showcase>
            <button type="button" class="vlog-showcase-nav" data-vlog-anterior aria-label="Experiencia anterior">←</button>
            <div class="vlog-showcase-pista" data-vlog-pista>
                @foreach($posts as $post)
                    @php
                        $indice = str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT);
                        $totalFmt = str_pad((string) $total, 2, '0', STR_PAD_LEFT);
                        $titulo = trim((string) ($post['destino'] ?? ''));
                        if ($titulo === '') {
                            $titulo = \Illuminate\Support\Str::limit((string) ($post['comentario'] ?? 'Experiencia Thaki'), 48, '');
                        }
                        $descripcion = \Illuminate\Support\Str::limit((string) ($post['comentario'] ?? ''), 180);
                        $likes = count($post['likes'] ?? []);
                        $comentarios = count($post['comentarios'] ?? []);
                        $fecha = ! empty($post['creado'])
                            ? \Illuminate\Support\Carbon::parse($post['creado'])->timezone('America/La_Paz')->format('d/m/Y')
                            : '—';
                        $enlace = route('publico.vlog').'#vlog-'.$post['id'];
                        $fotos = \App\Support\Vlog::urlsImagenes($post);
                    @endphp
                    <article class="vlog-showcase-tarjeta" data-vlog-slide>
                        <button type="button" class="vlog-showcase-media" data-vlog-abrir data-vlog-fotos='@json($fotos)' data-vlog-inicio="0">
                            <img src="{{ $fotos[0] ?? route('publico.vlog.imagen', $post['id']) }}" alt="Experiencia de {{ $post['nombre'] }}" loading="lazy">
                            <span class="vlog-vista">VISTA</span>
                            @if(count($fotos) > 1)
                                <span class="vlog-foto-badge">{{ count($fotos) }} fotos</span>
                            @endif
                        </button>
                        <div class="vlog-showcase-info">
                            <span class="vlog-indice">{{ $indice }} DE {{ $totalFmt }}</span>
                            <h3>{{ mb_strtoupper($titulo) }}</h3>
                            <p>{{ $descripcion }}</p>
                            <dl class="vlog-meta">
                                <div><x-icon name="users"/><span>{{ $post['nombre'] }}</span></div>
                                <div><x-icon name="bell"/><span>{{ $likes }} me gusta</span></div>
                                <div><x-icon name="chat"/><span>{{ $comentarios }} comentario{{ $comentarios === 1 ? '' : 's' }}</span></div>
                                <div><x-icon name="calendar"/><span>{{ $fecha }}</span></div>
                            </dl>
                            <a class="vlog-cta" href="{{ $enlace }}">VER EXPERIENCIA</a>
                        </div>
                    </article>
                @endforeach
            </div>
            <button type="button" class="vlog-showcase-nav" data-vlog-siguiente aria-label="Siguiente experiencia">→</button>
        </div>
    @else
        <div class="vlog-showcase-vacio">
            <h3>Pronto verás aquí las historias de nuestros viajeros</h3>
            <p>Cuando publiquen fotos en el vlog, aparecerán en este espacio con su experiencia.</p>
            <a class="boton" href="{{ route('publico.vlog') }}">Ir al vlog ↗</a>
        </div>
    @endif

    <div class="vlog-showcase-contacto" id="contacto">
        @if(!empty($empresa['email']))<a href="mailto:{{ $empresa['email'] }}">{{ $empresa['email'] }}</a>@endif
        @if(!empty($empresa['telefono']))<span>{{ $empresa['telefono'] }}</span>@endif
        @if(!empty($empresa['direccion']))<span>{{ $empresa['direccion'] }}</span>@endif
        @if(empty($empresa['email']) && empty($empresa['telefono']) && empty($empresa['direccion']))
            <a href="{{ route('acceso.registro') }}">Diseña tu viaje con Thaki ↗</a>
        @endif
    </div>
</section>
<x-vlog-lightbox/>
