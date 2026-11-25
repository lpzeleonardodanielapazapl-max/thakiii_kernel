@push('styles')<link rel="stylesheet" href="{{ asset('css/escaparate.css') }}?v={{ filemtime(public_path('css/escaparate.css')) }}" media="screen">@endpush
@push('scripts')<script src="{{ asset('js/escaparate.js') }}?v={{ filemtime(public_path('js/escaparate.js')) }}" defer></script>@endpush
<section class="planificador-publico" aria-label="Diseña tu viaje" id="planifica">
    <form method="GET" action="{{ route('publico.viaje') }}" data-planificador>
        <div class="planificador-barra">
            <label class="planificador-destino" for="idea-destino"><span><x-icon name="search"/> ¿Adónde quieres ir?</span><input id="idea-destino" name="destinos" list="destinos-inspiracion" maxlength="200" placeholder="Uyuni, La Paz, Amazonía…" value="{{ old('destinos', session('viaje_borrador.destinos')) }}" required></label>
            <div class="planificador-fechas"><label for="idea-inicio"><span><x-icon name="calendar"/> Inicio del viaje</span><input id="idea-inicio" type="date" name="fechaInicio" min="{{ now()->format('Y-m-d') }}" value="{{ old('fechaInicio', session('viaje_borrador.fechaInicio')) }}"></label><span aria-hidden="true">→</span><label for="idea-fin"><span>Regreso</span><input id="idea-fin" type="date" name="fechaFin" min="{{ now()->format('Y-m-d') }}" value="{{ old('fechaFin', session('viaje_borrador.fechaFin')) }}"></label></div>
            <label class="planificador-viajeros" for="idea-personas"><span><x-icon name="users"/> Viajeros</span><input id="idea-personas" type="number" name="personas" min="1" max="1000" value="{{ old('personas', session('viaje_borrador.personas', 2)) }}" required></label>
            <button type="submit" class="boton planificador-enviar">Diseñar mi viaje <x-icon name="arrow"/></button>
        </div>
        <datalist id="destinos-inspiracion">@foreach(config('publico.inspiraciones') as $idea)<option value="{{ $idea['nombre'] }}">{{ $idea['region'] }}</option>@endforeach</datalist>
        @php($sugeridos = \App\Support\LugaresVisitados::activos())
        @if(count($sugeridos))
        <div class="sugerencias-visitados" aria-label="Lugares más visitados">
            <div class="sugerencias-cabecera">
                <div>
                    <span class="etiqueta">SUGERENCIAS</span>
                    <strong>Lugares más visitados</strong>
                </div>
                <div class="destinos-controles" data-sugerencias-controles>
                    <button type="button" data-sugerencias-anterior aria-label="Ver sugerencia anterior">←</button>
                    <button type="button" data-sugerencias-siguiente aria-label="Ver más sugerencias">→</button>
                </div>
            </div>
            <div class="sugerencias-carrusel" data-sugerencias-carrusel>
                @foreach($sugeridos as $slug)
                    @php($idea = config('publico.inspiraciones.'.$slug))
                    @continue(empty($idea))
                    <a class="sugerencia-tarjeta" href="{{ route('publico.inspiracion', $slug) }}">
                        <img src="{{ asset('images/thaki/'.$idea['imagen']) }}" alt="{{ $idea['nombre'] }}" loading="lazy">
                        <div>
                            <span>{{ $idea['region'] }}</span>
                            <strong>{{ $idea['nombre'] }}</strong>
                            <p>{{ \Illuminate\Support\Str::limit($idea['descripcion'] ?? '', 78) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
        @if($errors->any())<div class="aviso error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </form>
</section>
