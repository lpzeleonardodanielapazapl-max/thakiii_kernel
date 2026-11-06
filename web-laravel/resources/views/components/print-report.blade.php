@props(['title', 'reference' => null, 'subtitle' => '', 'landscape' => false, 'client' => false])
@push('impresion')
<article id="informe-impresion" @class(['reporte-impresion', 'reporte-horizontal' => $landscape]) data-report-title="Thaki_Voyage_{{ \Illuminate\Support\Str::slug($title, '_') }}{{ $reference ? '_'.\Illuminate\Support\Str::slug($reference, '_') : '' }}_{{ now()->timezone('America/La_Paz')->format('Y-m-d') }}">
    <header class="reporte-cabecera">
        <div class="reporte-marca"><x-icon name="mountain"/><div><strong>THAKI VOYAGE</strong><span>Turismo a medida · Bolivia</span></div></div>
        <div class="reporte-emision"><strong>{{ $client ? 'DOCUMENTO DE VIAJE' : 'DOCUMENTO DE GESTIÓN' }}</strong><span>Emitido el {{ now()->timezone('America/La_Paz')->format('d/m/Y · H:i') }}</span><span>Hora de Bolivia (UTC−04:00)</span></div>
    </header>
    <div class="reporte-titulo"><p>{{ $client ? 'TU VIAJE A MEDIDA' : 'GESTIÓN INTERNA' }}</p><h1>{{ $title }}</h1>@if ($subtitle)<div>{{ $subtitle }}</div>@endif</div>
    @if ($reference)<p class="reporte-referencia"><strong>Referencia:</strong> {{ $reference }}</p>@endif
    {{ $slot }}
    <footer class="reporte-cierre"><span>THAKI VOYAGE · {{ $client ? 'Propuesta personalizada de viaje' : 'Registro de gestión interna' }}</span><span>Fin del documento</span></footer>
</article>
@endpush
