@extends('layouts.publico')
@section('contenido')
@section('publico-clase', 'portada-viajes')
<section class="hero hero-con-video" style="--foto:url('{{ asset('images/thaki/illimani.jpg') }}')">
    <div class="hero-video-fondo" aria-hidden="true">
        <video autoplay muted loop playsinline preload="auto" poster="{{ asset('images/thaki/illimani.jpg') }}">
            <source src="{{ asset('videos/hero-uyuni.mp4') }}" type="video/mp4">
        </video>
    </div>
    <div class="hero-contenido"><span class="etiqueta clara">BOLIVIA · VIAJES A MEDIDA</span><h1>Cada viajero,<br><em>su propio camino.</em></h1><p>Montañas, culturas y horizontes extraordinarios.<br>Cuéntanos qué te inspira. Diseñemos tu viaje juntos.</p><a class="boton" href="{{ route('publico.asistente') }}">Personaliza tu ruta <span>↗</span></a></div>
    <a class="hero-pie" href="#inspiraciones">DESCUBRE LO QUE TE ESPERA <span>↓</span></a>
</section>
<script>
(function () {
    var video = document.querySelector('.hero-video-fondo video');
    if (!video) return;
    var inicio = 12, fin = 40;
    video.addEventListener('loadedmetadata', function () {
        if (video.duration && video.duration < fin) fin = Math.max(video.duration - 0.2, 0);
        if (video.duration && video.duration > inicio + 4) video.currentTime = inicio;
        video.play();
    });
    video.addEventListener('timeupdate', function () {
        if (video.currentTime >= fin) video.currentTime = inicio;
    });
})();
</script>
<x-planificador-publico/>
<section class="seccion" id="inspiraciones"><div class="encabezado-seccion"><div><span class="etiqueta">UN PAÍS, MUCHOS CAMINOS</span><h2>¿Qué Bolivia<br><em>te gustaría descubrir?</em></h2></div><p>Estas ideas son un punto de partida. Tu ruta, fechas y experiencias se construyen contigo.</p></div><div class="destinos-cabecera"><span>Ideas para comenzar tu ruta</span><div class="destinos-controles" data-destinos-controles hidden><button type="button" data-destinos-anterior aria-label="Ver destinos anteriores">←</button><button type="button" data-destinos-siguiente aria-label="Ver más destinos">→</button></div></div><div class="inspiraciones" data-destinos-carrusel>
@foreach(['uyuni', 'la-paz', 'amazonia', 'cultura'] as $slug)
@php($idea = config('publico.inspiraciones.'.$slug))
<a class="tarjeta-destino tarjeta-editorial" href="{{ route('publico.inspiracion', $slug) }}"><img src="{{ asset('images/thaki/'.$idea['imagen']) }}" alt="{{ $idea['nombre'] }}" loading="lazy"><div><span class="etiqueta clara">{{ $idea['region'] }}</span><h3>{{ $idea['nombre'] }}</h3><span class="flecha">↗</span></div></a>
@endforeach
</div></section>
@if(count($destinos))
<section class="seccion catalogo"><span class="etiqueta">CONOCE MÁS</span><h2>Destinos para <em>tu próxima ruta.</em></h2><div class="rejilla tres">@foreach($destinos as $destino)<a class="tarjeta" href="{{ route('publico.destino', $destino['codigo']) }}"><span class="etiqueta">{{ $destino['region'] ?? 'BOLIVIA' }}</span><h3>{{ $destino['nombre'] }}</h3><p>{{ $destino['descripcion'] ?? '' }}</p><span>Conocer el destino ↗</span></a>@endforeach</div></section>
@elseif($errorCatalogo)<p class="aviso ancho">El catálogo no está disponible por el momento. Puedes explorar nuestras ideas de viaje.</p>@endif
<section class="nosotros seccion" id="nosotros"><div class="foto-editorial"><img src="{{ asset('images/thaki/cultura.jpg') }}" alt="Tradiciones y encuentros en Bolivia" loading="lazy"><span>El camino también<br>son las personas.</span></div><div><span class="etiqueta">NUESTRA ESENCIA</span><h2>Conocer un lugar.<br><em>Sentirse parte.</em></h2><p>{{ $empresa['historia'] ?? 'En Thaki Voyage creemos que un viaje comienza escuchándote. Tus intereses, tu ritmo y tus ganas de descubrir nos ayudan a construir una experiencia personal en Bolivia.' }}</p><p>Habla con un asesor, comparte tus ideas y revisa una propuesta pensada para ti, con el itinerario y los servicios detallados.</p><a class="enlace" href="{{ route('acceso.registro') }}">Hablemos de tu viaje ↗</a></div></section>
<section class="proceso seccion" id="como-viajar"><span class="etiqueta">ASÍ EMPIEZA TU CAMINO</span><h2>De una idea a <em>tu viaje.</em></h2><div class="rejilla cuatro">@foreach(['Cuéntanos tu idea' => 'Crea tu cuenta y comparte fechas, viajeros, presupuesto e intereses.', 'Conversa con tu asesor' => 'Ajusta tu ruta, alojamiento, alimentación y transporte.', 'Revisa tu propuesta' => 'Consulta el itinerario día a día y comunica tu aceptación.', 'Prepara tu salida' => 'Coordina el anticipo externo del 50 %. El personal verifica el pago y la confirmación.'] as $titulo => $texto)<article><span class="numero">0{{ $loop->iteration }}</span><h3>{{ $titulo }}</h3><p>{{ $texto }}</p></article>@endforeach</div><p class="nota">El saldo se paga a la llegada. Los pagos se coordinan con el asesor; esta web no procesa cobros en línea.</p></section>
@if(count($testimonios))<section class="seccion"><span class="etiqueta">VOCES DEL CAMINO</span><h2>Historias <em>compartidas.</em></h2><div class="rejilla tres">@foreach($testimonios as $testimonio)<blockquote class="tarjeta"><p>“{{ $testimonio['contenido'] }}”</p><cite>{{ $testimonio['nombre'] }}</cite></blockquote>@endforeach</div></section>@endif
<x-vlog-showcase :posts="$vlogs ?? []" :empresa="$empresa ?? []"/>
@endsection
