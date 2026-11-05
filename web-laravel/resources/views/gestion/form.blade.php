@extends('layouts.admin')
@section('titulo', ($editing ? 'Editar ' : 'Registrar ').$meta['singular'])
@section('contenido')
    <x-page-header :title="($editing ? 'Editar ' : 'Registrar ').$meta['singular']" :description="$meta['description']" eyebrow="GESTIÓN · DATOS DEL REGISTRO">
        <a class="boton boton-secundario" href="{{ route($meta['modulo'].'.index') }}"><x-icon name="back"/> {{ $meta['title'] }}</a>
    </x-page-header>
    @if (session('exito'))<div class="aviso" role="status">{{ session('exito') }}</div>@endif
    @if ($errors->any())
        <div class="aviso error" role="alert"><strong>Revisa los datos antes de continuar.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if (! $editing && in_array($meta['modulo'], ['empleados', 'clientes']))
        <div class="banda-contexto"><span class="icono-destacado"><x-icon name="users"/></span><div><strong>Primero, la persona.</strong><p>Selecciona una persona existente o registra sus datos personales antes de continuar.</p></div><a class="boton boton-secundario" href="{{ route('personas.create', ['volver' => $meta['modulo']]) }}">Registrar persona <x-icon name="plus"/></a></div>
    @endif
    @if ($meta['modulo'] === 'empleados')
        <div class="enlaces-contexto"><a href="{{ route('cargos.index') }}">Gestionar cargos y responsabilidades <x-icon name="arrow"/></a><a href="{{ route('departamentos.index') }}">Gestionar departamentos <x-icon name="arrow"/></a></div>
        <p class="nota">El cargo describe su trabajo; las funciones indican si puede actuar como guía, asesor o conductor. Estas funciones no crean roles de acceso.</p>
    @endif
    <section class="formulario-panel">
        <h2>{{ $editing ? 'Actualiza los detalles' : 'Completa los detalles' }}</h2>
        <p class="nota">Los campos con * son obligatorios. {{ $editing ? 'El código del registro se conserva.' : 'El código se genera automáticamente.' }}</p>
        <form method="POST" action="{{ $editing ? route($meta['modulo'].'.update', ['codigo' => $registro[$meta['key']]]) : route($meta['modulo'].'.store') }}" data-save-form>
            @csrf
            @if ($editing) @method('PUT') @endif
            @if ($meta['modulo'] === 'personas' && in_array(request('volver'), ['empleados', 'clientes']))<input type="hidden" name="volver" value="{{ request('volver') }}">@endif
            <div class="formulario-grid">
                @foreach ($meta['fields'] as $nombre => $campo)
                    @php
                        $readonly = $editing && ($campo['immutable'] ?? false);
                        $valor = $readonly ? ($registro[$nombre] ?? '') : old($nombre, $registro[$nombre] ?? $campo['default'] ?? '');
                        $reglas = $editing ? ($campo['editRules'] ?? $campo['rules']) : $campo['rules'];
                        $required = in_array('required', $reglas);
                        $maxRule = collect($reglas)->first(fn ($rule) => str_starts_with($rule, 'max:'));
                        $maxLength = $maxRule ? substr($maxRule, 4) : null;
                        $opciones = isset($campo['catalogo']) ? ($catalogos[$campo['catalogo']] ?? []) : ($campo['options'] ?? []);
                    @endphp
                    <div @class(['ancho-completo' => $campo['type'] === 'textarea'])>
                        @if ($campo['type'] === 'checkbox')
                            <input type="hidden" name="{{ $nombre }}" value="0">
                            <label class="check-tarjeta" for="{{ $nombre }}"><input id="{{ $nombre }}" name="{{ $nombre }}" type="checkbox" value="1" @checked($valor)><span>{{ $campo['label'] }}</span></label>
                        @else
                            <label for="{{ $nombre }}">{{ $campo['label'] }} {{ $required ? '*' : '' }}</label>
                            @if ($campo['type'] === 'select')
                                @if ($readonly)<input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">@endif
                                <select id="{{ $nombre }}" @if (! $readonly) name="{{ $nombre }}" @endif @required($required) @disabled($readonly) @error($nombre) aria-invalid="true" @enderror>
                                    <option value="">Selecciona una opción</option>
                                    @foreach ($opciones as $codigo => $etiqueta)<option value="{{ $codigo }}" @selected((string) $valor === (string) $codigo)>{{ $etiqueta }}</option>@endforeach
                                </select>
                                @if (isset($campo['catalogo']) && empty($opciones))<small class="campo-error">No hay registros disponibles. Registra primero {{ $campo['catalogo'] }} desde su apartado.</small>@endif
                                @if ($readonly)<small>La persona vinculada se conserva. Puedes editar sus datos desde Personas.</small>@endif
                            @elseif ($campo['type'] === 'textarea')
                                <textarea id="{{ $nombre }}" name="{{ $nombre }}" rows="4" maxlength="{{ $maxLength }}" @required($required)>{{ $valor }}</textarea>
                            @else
                                <input id="{{ $nombre }}" name="{{ $nombre }}" type="{{ $campo['type'] }}" @if ($campo['type'] !== 'password') value="{{ $valor }}" @endif
                                    @if ($campo['type'] === 'password') autocomplete="new-password" minlength="8" @endif
                                    @if ($maxLength && $campo['type'] !== 'number') maxlength="{{ $maxLength }}" @endif
                                    @if (isset($campo['step'])) step="{{ $campo['step'] }}" @endif @if (isset($campo['min'])) min="{{ $campo['min'] }}" @endif
                                    @required($required) @readonly($readonly) @error($nombre) aria-invalid="true" @enderror>
                                @if ($campo['type'] === 'password' && $editing)<small>Deja los dos campos vacíos para conservar la contraseña actual.</small>@endif
                            @endif
                        @endif
                        @error($nombre)<small class="campo-error">{{ $message }}</small>@enderror
                    </div>
                    @if ($campo['type'] === 'password')
                        <div><label for="password_confirmation">Confirmar contraseña {{ $required ? '*' : '' }}</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="255" @required($required)></div>
                    @endif
                @endforeach
            </div>
            <div class="formulario-acciones"><button class="boton" type="submit"><x-icon name="plus"/> {{ $editing ? 'Guardar cambios' : 'Guardar registro' }}</button><a class="boton boton-secundario" href="{{ $editing ? route($meta['modulo'].'.show', ['codigo' => $registro[$meta['key']]]) : route($meta['modulo'].'.index') }}">Cancelar</a></div>
        </form>
    </section>
@endsection
