@extends('layouts.admin')
@section('titulo', 'Editar unidad')
@section('contenido')
    <x-page-header :title="'Editar unidad '.($unidad['codigoUnico'] ?? $unidad['id'])" :description="$producto['nombre']" eyebrow="INVENTARIO · CONDICIÓN DEL EQUIPO">
        <a class="boton boton-secundario" href="{{ route('inventario.unidades', ['codigo' => $producto['codigo']]) }}"><x-icon name="back"/> Volver a unidades</a>
    </x-page-header>
    @if ($errors->any())<div class="aviso error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if ($unidad['fechaBaja'])<div class="aviso">Esta unidad tiene baja registrada. Su historial se conserva sin cambios.</div>
    @else
        <section class="formulario-panel"><h2>Condición y detalles</h2><p class="nota">El código y el almacén ya asignados se conservan. Solicitar una baja no registra una baja definitiva.</p>
            <form method="POST" action="{{ route('inventario.unidades.actualizar', ['codigo' => $producto['codigo'], 'id' => $unidad['id']]) }}">@csrf @method('PUT')
                <div class="formulario-grid">
                    <div><label for="codigoUnico">Código único *</label><input id="codigoUnico" name="codigoUnico" value="{{ old('codigoUnico', $unidad['codigoUnico']) }}" maxlength="50" @readonly($unidad['codigoUnico']) required></div>
                    <div><label for="codAlmacen">Almacén *</label>
                        @if ($unidad['codAlmacen'])<input type="hidden" name="codAlmacen" value="{{ $unidad['codAlmacen'] }}"><input id="codAlmacen" value="{{ $unidad['almacen'] }}" readonly>
                        @else<select id="codAlmacen" name="codAlmacen" required><option value="">Selecciona el almacén</option>@foreach ($almacenes as $almacen)<option value="{{ $almacen['codigo'] }}" @selected(old('codAlmacen') === $almacen['codigo'])>{{ $almacen['nombre'] }}</option>@endforeach</select>@endif
                    </div>
                    <div><label for="estadoFisico">Condición física *</label><input id="estadoFisico" name="estadoFisico" value="{{ old('estadoFisico', $unidad['estadoFisico']) }}" maxlength="20" required></div>
                    <div><label for="calificacion">Calificación (opcional)</label><input id="calificacion" name="calificacion" type="number" min="0" max="2147483647" step="1" value="{{ old('calificacion', $unidad['calificacion']) }}"></div>
                    <div><label for="fechaAlta">Fecha de alta *</label><input id="fechaAlta" name="fechaAlta" type="date" max="{{ now()->toDateString() }}" value="{{ old('fechaAlta', $unidad['fechaAlta']) }}" required></div>
                    <fieldset class="atencion-unidad"><legend>Atención requerida</legend>@foreach (['requiereArreglo' => 'Requiere arreglo', 'requiereCambio' => 'Requiere cambio', 'requiereBaja' => 'Solicitar baja'] as $campo => $label)<input type="hidden" name="{{ $campo }}" value="0"><label><input type="checkbox" name="{{ $campo }}" value="1" @checked(old($campo, $unidad[$campo]))> {{ $label }}</label>@endforeach</fieldset>
                    <div class="ancho-completo"><label for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" rows="4" maxlength="2000">{{ old('observaciones', $unidad['observaciones']) }}</textarea></div>
                </div>
                <div class="formulario-acciones"><button class="boton" type="submit">Guardar cambios</button><a class="boton boton-secundario" href="{{ route('inventario.unidades', ['codigo' => $producto['codigo']]) }}">Cancelar</a></div>
            </form>
        </section>
    @endif
@endsection
