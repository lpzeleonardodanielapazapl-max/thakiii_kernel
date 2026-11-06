@props(['messages', 'contact', 'subtitle' => 'Conversación privada', 'back', 'action', 'enabled' => true, 'clientId' => null, 'label' => 'Tu mensaje'])
@php
    $iniciales = collect(preg_split('/\s+/', trim($contact)))->filter()->take(2)->map(fn($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
    $ultimoGrupo = null;
    $hoy = now('America/La_Paz')->toDateString();
    $ayer = now('America/La_Paz')->subDay()->toDateString();
@endphp
<section class="thaki-chat" data-chat aria-label="Conversación con {{ $contact }}">
    <header class="chat-cabecera">
        <a class="chat-volver" href="{{ $back }}" aria-label="Volver"><x-icon name="back"/></a>
        <span class="chat-avatar" aria-hidden="true">{{ $iniciales ?: 'TV' }}</span>
        <div class="chat-contacto"><h2>{{ $contact }}</h2><p>{{ $subtitle }}</p></div>
        <a class="chat-actualizar" href="{{ url()->current() }}" aria-label="Actualizar conversación" title="Actualizar conversación"><x-icon name="refresh"/></a>
    </header>
    <div class="chat-historial" data-chat-historial role="region" aria-label="Historial de mensajes" tabindex="0">
        <p class="chat-aviso"><x-icon name="shield"/> Conversación privada con el equipo Thaki</p>
        <p class="chat-limite">Últimos 100 mensajes · Usa actualizar para ver nuevas respuestas.</p>
        @forelse(array_reverse($messages) as $mensaje)
            @php
                $propio = (string)$mensaje['remitente'] === (string)session('usuario.id');
                $fecha = !empty($mensaje['fecha']) ? \Illuminate\Support\Carbon::parse($mensaje['fecha'], 'America/La_Paz')->timezone('America/La_Paz') : null;
                $grupo = $fecha?->toDateString() ?? 'sin-fecha';
                $remitente = $propio ? 'Tú' : ($clientId !== null && (string)$mensaje['remitente'] === (string)$clientId ? $contact : 'Equipo Thaki');
            @endphp
            @if($grupo !== $ultimoGrupo)
                <div class="chat-dia"><span>{{ match($grupo) { $hoy => 'Hoy', $ayer => 'Ayer', 'sin-fecha' => 'Fecha no registrada', default => $fecha->format('d/m/Y') } }}</span></div>
                @php($ultimoGrupo = $grupo)
            @endif
            <article @class(['chat-burbuja', 'chat-burbuja-propia' => $propio]) data-chat-message>
                <span class="chat-remitente">{{ $remitente }}</span>
                <p>{{ $mensaje['contenido'] }}</p>
                @if($fecha)<time datetime="{{ $fecha->toIso8601String() }}" title="{{ $fecha->format('d/m/Y H:i') }}">{{ $fecha->format('H:i') }}</time>@endif
            </article>
        @empty
            <div class="chat-vacio"><span><x-icon name="chat"/></span><h3>Una buena conversación<br>es el comienzo del viaje.</h3><p>Comparte tus ideas, resuelve dudas y conversa sobre los próximos pasos.</p></div>
        @endforelse
    </div>
    <button type="button" class="chat-ir-final" data-chat-final hidden aria-label="Ir a los mensajes más recientes">↓ <span>Últimos mensajes</span></button>
    @if($enabled)
    <form class="chat-compositor" method="POST" action="{{ $action }}" data-chat-form>
        @csrf
        <label class="solo-lectores" for="chat-contenido">{{ $label }}</label>
        <div class="chat-escritura"><textarea id="chat-contenido" name="contenido" rows="1" maxlength="4000" required placeholder="Escribe un mensaje…" data-chat-input @if($errors->has('contenido')) aria-invalid="true" aria-describedby="error-contenido" @endif>{{ old('contenido') }}</textarea><button type="submit" class="chat-enviar" aria-label="Enviar mensaje" data-chat-send data-submitting-label="Enviando…"><x-icon name="send"/><span>Enviar</span></button></div>
        <x-error-campo name="contenido"/>
        <small class="chat-teclado">Enter para enviar · Mayús + Enter para una nueva línea</small>
    </form>
    @else
        <div class="chat-pendiente">{{ $slot }}</div>
    @endif
</section>
