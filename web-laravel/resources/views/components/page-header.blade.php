@props(['title', 'description' => '', 'eyebrow' => 'ESPACIO DE GESTIÓN'])
<div class="encabezado-pagina">
    <div><p class="sobre-titulo">{{ $eyebrow }}</p><h1>{{ $title }}</h1>@if ($description)<p class="descripcion">{{ $description }}</p>@endif</div>
    @if ($slot->isNotEmpty())<div class="acciones-pagina no-imprimir">{{ $slot }}</div>@endif
</div>
