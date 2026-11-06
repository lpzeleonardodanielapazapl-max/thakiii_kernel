@extends('layouts.admin')

@section('titulo', 'Unidades físicas')

@section('contenido')
    <x-page-header :title="$producto['nombre'] ?? 'Unidades del producto'" :description="($producto['codigo'] ?? '').' · Cada equipo tiene su propio código.'" eyebrow="EQUIPAMIENTO · UNIDADES FÍSICAS">
        <a class="boton boton-secundario" href="{{ route('inventario.index') }}"><x-icon name="back"/> Inventario</a>
        @if ($producto && $producto['estado'] === 'ACTIVO' && count($almacenes))
            <a class="boton" href="#registrar-unidad"><x-icon name="plus"/> Registrar unidad</a>
        @endif
    </x-page-header>
    @if ($producto)
        <div class="enlaces-contexto no-imprimir"><a href="{{ route('inventario.analizar', ['codigo' => $producto['codigo']]) }}">Analizar reposición <x-icon name="arrow"/></a><a href="{{ route('inventario.abc') }}">Ver clasificación ABC <x-icon name="arrow"/></a></div>
    @endif

    @if (session('exito'))
        <div class="aviso" role="status">{{ session('exito') }}</div>
    @endif
    @if ($errorApi)
        <div class="aviso error" role="alert">
            <p>{{ $errorApi }}</p>
            <a href="{{ url()->current() }}">Volver a intentar</a>
        </div>
    @endif
    @if ($errors->any())
        <div class="aviso error" role="alert">
            <strong>No se pudo registrar la unidad:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($producto)
        <section class="formulario-panel" data-table data-print-target="tabla-impresion">
            <h2>Unidades registradas ({{ count($unidades) }})</h2>
            <p class="nota">Este listado muestra la condición de los equipos. La disponibilidad depende también de sus salidas y recepciones.</p>
            <div class="acciones-pagina no-imprimir"><button class="boton boton-secundario boton-pequeno" type="button" data-print><x-icon name="print"/> Imprimir listado</button><a class="enlace-icono" href="{{ url()->current() }}"><x-icon name="refresh"/> Actualizar</a></div>
            <x-table-tools label="Buscar código o condición" :filters="['arreglo' => 'Requiere arreglo', 'cambio' => 'Requiere cambio', 'baja-solicitada' => 'Baja solicitada', 'baja' => 'Baja registrada']"/>
            <div class="tabla-scroll">
                <table class="tabla-inventario">
                    <caption class="solo-lectores">Unidades físicas de {{ $producto['nombre'] }}</caption>
                    <thead><tr>
                        <th scope="col">Código</th><th scope="col">Almacén</th>
                        <th scope="col">Condición</th><th scope="col">Calificación</th>
                        <th scope="col">Atención</th><th scope="col">Fechas</th><th scope="col">Observaciones</th><th scope="col" class="no-imprimir">Acciones</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($unidades as $unidad)
                            <tr data-row data-print-key="{{ $loop->index }}" data-tags="{{ $unidad['requiereArreglo'] ? 'arreglo' : '' }} {{ $unidad['requiereCambio'] ? 'cambio' : '' }} {{ $unidad['requiereBaja'] ? 'baja-solicitada' : '' }} {{ $unidad['fechaBaja'] ? 'baja' : '' }}">
                                <td><strong>{{ $unidad['codigoUnico'] ?? 'Sin código' }}</strong></td>
                                <td>{{ $unidad['almacen'] ?? 'Sin almacén' }}</td>
                                <td>{{ $unidad['estadoFisico'] ?? 'Sin evaluar' }}</td>
                                <td>{{ $unidad['calificacion'] ?? 'Sin evaluar' }}</td>
                                <td>
                                    @if ($unidad['fechaBaja'])
                                        <span class="etiqueta-unidad">Baja registrada</span>
                                    @else
                                        @if ($unidad['requiereArreglo'])<span class="etiqueta-unidad">Arreglo</span>@endif
                                        @if ($unidad['requiereCambio'])<span class="etiqueta-unidad">Cambio</span>@endif
                                        @if ($unidad['requiereBaja'])<span class="etiqueta-unidad">Baja solicitada</span>@endif
                                        @if (! $unidad['requiereArreglo'] && ! $unidad['requiereCambio'] && ! $unidad['requiereBaja'])
                                            Sin pendientes registrados
                                        @endif
                                    @endif
                                </td>
                                <td>Alta: {{ $unidad['fechaAlta'] ?? 'Sin fecha' }}
                                    @if ($unidad['fechaBaja'])<br>Baja: {{ $unidad['fechaBaja'] }}@endif
                                </td>
                                <td class="observacion-unidad">{{ $unidad['observaciones'] ?? '—' }}</td>
                                <td class="no-imprimir">@if (! $unidad['fechaBaja'])<a class="accion-enlace" href="{{ route('inventario.unidades.editar', ['codigo' => $producto['codigo'], 'id' => $unidad['id']]) }}">Editar condición ↗</a>@else<span class="badge badge-gris">Historial</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">Este producto todavía no tiene unidades físicas registradas.</td></tr>
                        @endforelse
                        <tr data-no-results hidden><td colspan="8" class="estado-vacio">No encontramos unidades con estos filtros.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        @if ($producto['estado'] !== 'ACTIVO')
            <div class="aviso error">El producto está inactivo. No se pueden registrar nuevas unidades.</div>
        @elseif (empty($almacenes))
            <div class="aviso error">No hay un almacén activo registrado. Solicita al administrador revisar el almacén principal.</div>
        @else
            <section class="formulario-panel no-imprimir" id="registrar-unidad">
                <h2>Registrar una unidad</h2>
                <p class="nota">Los campos con * son obligatorios. Usa el código que identifica físicamente el equipo.</p>
                <form method="POST" action="{{ route('inventario.unidades.guardar', ['codigo' => $producto['codigo']]) }}">
                    @csrf
                    <div class="formulario-grid">
                        <div>
                            <label for="codigoUnico">Código único *</label>
                            <input id="codigoUnico" name="codigoUnico" maxlength="50" value="{{ old('codigoUnico') }}" placeholder="Ejemplo: CD001" required>
                        </div>
                        <div>
                            <label for="codAlmacen">Almacén *</label>
                            <select id="codAlmacen" name="codAlmacen" required>
                                <option value="">Selecciona el almacén</option>
                                @foreach ($almacenes as $almacen)
                                    <option value="{{ $almacen['codigo'] }}" @selected(old('codAlmacen', count($almacenes) === 1 ? $almacen['codigo'] : '') === $almacen['codigo'])>{{ $almacen['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="estadoFisico">Condición física *</label>
                            <input id="estadoFisico" name="estadoFisico" maxlength="20" value="{{ old('estadoFisico') }}" placeholder="Ejemplo: BUENO" required>
                        </div>
                        <div>
                            <label for="calificacion">Calificación (opcional)</label>
                            <input id="calificacion" name="calificacion" type="number" min="0" max="2147483647" step="1" value="{{ old('calificacion') }}" aria-describedby="ayuda-calificacion">
                            <small id="ayuda-calificacion">Déjala vacía si el equipo aún no tiene una calificación acordada.</small>
                        </div>
                        <div>
                            <label for="fechaAlta">Fecha de alta *</label>
                            <input id="fechaAlta" name="fechaAlta" type="date" max="{{ now()->toDateString() }}" value="{{ old('fechaAlta', now()->toDateString()) }}" required>
                        </div>
                        <fieldset class="atencion-unidad">
                            <legend>Atención requerida</legend>
                            @foreach (['requiereArreglo' => 'Requiere arreglo', 'requiereCambio' => 'Requiere cambio', 'requiereBaja' => 'Solicitar baja'] as $campo => $etiqueta)
                                <label><input type="checkbox" name="{{ $campo }}" value="1" @checked(old($campo))> {{ $etiqueta }}</label>
                            @endforeach
                            <small>Solicitar una baja no registra su aprobación ni una fecha de baja.</small>
                        </fieldset>
                        <div class="ancho-completo">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" maxlength="2000" rows="3">{{ old('observaciones') }}</textarea>
                        </div>
                    </div>
                    <div class="formulario-acciones"><button class="boton" type="submit"><x-icon name="plus"/> Registrar unidad</button><a class="boton boton-secundario" href="{{ route('inventario.unidades', ['codigo' => $producto['codigo']]) }}">Limpiar formulario</a></div>
                </form>
            </section>
        @endif
    @endif
    @if ($producto)
        <x-print-report title="Unidades físicas del producto" :reference="$producto['codigo']" :subtitle="$producto['nombre']" :landscape="true">
            <p class="reporte-alcance"><span data-report-count>{{ count($unidades) }} de {{ count($unidades) }} registros</span> · <span data-report-filters>Sin filtros adicionales.</span></p>
            <table id="tabla-impresion" class="reporte-tabla reporte-unidades"><thead><tr><th scope="col">Código único</th><th scope="col">Almacén</th><th scope="col">Condición</th><th scope="col">Calificación</th><th scope="col">Atención requerida</th><th scope="col">Fechas</th><th scope="col">Observaciones</th></tr></thead><tbody>
                @foreach ($unidades as $unidad)
                    <tr data-print-key="{{ $loop->index }}"><td>{{ $unidad['codigoUnico'] ?? 'Sin código' }}</td><td>{{ $unidad['almacen'] ?? 'Sin almacén' }}</td><td>{{ $unidad['estadoFisico'] ?? 'Sin evaluar' }}</td><td>{{ $unidad['calificacion'] ?? 'Sin evaluar' }}</td>
                        <td>@if ($unidad['fechaBaja'])Baja registrada @else{{ collect(['requiereArreglo' => 'Arreglo', 'requiereCambio' => 'Cambio', 'requiereBaja' => 'Baja solicitada'])->filter(fn ($label, $flag) => $unidad[$flag])->implode(' · ') ?: 'Sin pendientes registrados' }}@endif</td>
                        <td>Alta: <x-print-value :value="$unidad['fechaAlta']" type="date"/>@if ($unidad['fechaBaja'])<br>Baja: <x-print-value :value="$unidad['fechaBaja']" type="date"/>@endif</td><td><span class="reporte-texto">{{ $unidad['observaciones'] ?? '—' }}</span></td>
                    </tr>
                @endforeach
                <tr data-report-empty @if (count($unidades)) hidden @endif><td colspan="7">No hay unidades para el alcance seleccionado.</td></tr>
            </tbody></table>
            <p class="reporte-nota">El informe describe la condición registrada. La disponibilidad debe verificarse con las salidas y recepciones. Una solicitud de baja no equivale a una baja definitiva.</p>
        </x-print-report>
    @endif
@endsection
