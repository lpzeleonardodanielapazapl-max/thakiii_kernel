@push('styles')<link rel="stylesheet" href="{{ asset('css/escaparate.css') }}?v={{ filemtime(public_path('css/escaparate.css')) }}" media="screen">@endpush
@push('scripts')<script src="{{ asset('js/escaparate.js') }}?v={{ filemtime(public_path('js/escaparate.js')) }}" defer></script>@endpush
<section class="planificador-publico" aria-label="Diseña tu viaje" id="planifica">
    <form method="GET" action="{{ route('publico.planificar') }}" data-planificador>
        <div class="planificador-barra">
            <label class="planificador-destino" for="idea-destino"><span><x-icon name="search"/> ¿Adónde quieres ir?</span><input id="idea-destino" name="destinos" list="destinos-inspiracion" maxlength="200" placeholder="Uyuni, La Paz, Amazonía…" value="{{ old('destinos', session('viaje_borrador.destinos')) }}" required></label>
            <div class="planificador-fechas"><label for="idea-inicio"><span><x-icon name="calendar"/> Inicio del viaje</span><input id="idea-inicio" type="date" name="fechaInicio" min="{{ now()->format('Y-m-d') }}" value="{{ old('fechaInicio', session('viaje_borrador.fechaInicio')) }}"></label><span aria-hidden="true">→</span><label for="idea-fin"><span>Regreso</span><input id="idea-fin" type="date" name="fechaFin" min="{{ now()->format('Y-m-d') }}" value="{{ old('fechaFin', session('viaje_borrador.fechaFin')) }}"></label></div>
            <label class="planificador-viajeros" for="idea-personas"><span><x-icon name="users"/> Viajeros</span><input id="idea-personas" type="number" name="personas" min="1" max="1000" value="{{ old('personas', session('viaje_borrador.personas', 2)) }}" required></label>
            <button type="submit" class="boton planificador-enviar">Diseñar mi viaje <x-icon name="arrow"/></button>
        </div>
        <datalist id="destinos-inspiracion">@foreach(config('publico.inspiraciones') as $idea)<option value="{{ $idea['nombre'] }}">{{ $idea['region'] }}</option>@endforeach</datalist>
        <details class="planificador-extra"><summary>Otras preferencias <span aria-hidden="true">＋</span></summary><div class="planificador-opciones">
            <label for="idea-presupuesto">Presupuesto total (opcional)<input id="idea-presupuesto" type="number" name="presupuesto" min="0" max="9999999999.99" step="0.01" value="{{ old('presupuesto', session('viaje_borrador.presupuesto')) }}" placeholder="Por definir"></label>
            <label for="idea-moneda">Moneda<select id="idea-moneda" name="moneda">@foreach(['BOB','USD','EUR'] as $moneda)<option @selected(old('moneda', session('viaje_borrador.moneda', 'USD')) === $moneda)>{{ $moneda }}</option>@endforeach</select></label>
            @foreach(['hotel' => 'Alojamiento', 'alimentacion' => 'Alimentación', 'transporte' => 'Transporte'] as $campo => $titulo)<label for="idea-{{ $campo }}">{{ $titulo }}<input id="idea-{{ $campo }}" name="{{ $campo }}" maxlength="200" value="{{ old($campo, session('viaje_borrador.'.$campo)) }}" placeholder="Tus preferencias"></label>@endforeach
        </div></details>
        <p class="planificador-nota">Viajes a medida. Tu asesor confirmará contigo el itinerario, los servicios y el precio.</p>
        @if($errors->any())<div class="aviso error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </form>
</section>
