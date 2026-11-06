@extends('layouts.portal')
@section('titulo', 'Mi perfil')
@section('portal')
@php
    $nombrePerfil = trim(($perfil['nombres'] ?? '').' '.($perfil['apellidos'] ?? '')) ?: session('usuario.nombre', 'Viajero');
    $inicialesPerfil = collect(preg_split('/\s+/', $nombrePerfil))->filter()->take(2)->map(fn($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
@endphp
<header class="perfil-hero">
    <span class="perfil-avatar" aria-hidden="true">{{ $inicialesPerfil }}</span>
    <div><span class="etiqueta">TU ESPACIO PERSONAL</span><h1>Tu perfil de viajero.</h1><p>{{ $nombrePerfil }} · Actualiza tus datos para organizar tu próxima experiencia.</p></div>
</header>
<x-perfil-progreso :estado="$perfil['completitud'] ?? []" :en-perfil="true"/>
<form class="formulario tarjeta perfil-formulario" method="POST" action="{{ route('portal.perfil.guardar') }}">
    @csrf @method('PUT')
    <fieldset class="perfil-seccion"><legend><span>01</span>Datos personales</legend><div class="rejilla dos">
        @foreach(['nombres' => 'Nombres', 'apellidos' => 'Apellidos'] as $campo => $titulo)
        <label for="{{ $campo }}">{{ $titulo }} *<input id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $perfil[$campo] ?? '') }}" maxlength="100" autocomplete="{{ $campo === 'nombres' ? 'given-name' : 'family-name' }}" required @if($errors->has($campo)) aria-invalid="true" aria-describedby="error-{{ $campo }}" @endif><x-error-campo :name="$campo"/></label>
        @endforeach
        <label for="fechaNacimiento">Fecha de nacimiento *<input id="fechaNacimiento" type="date" name="fechaNacimiento" max="{{ now()->format('Y-m-d') }}" value="{{ old('fechaNacimiento', $perfil['fechaNacimiento'] ?? '') }}" autocomplete="bday"><x-error-campo name="fechaNacimiento"/></label>
        <label for="genero">Género (opcional)<input id="genero" name="genero" maxlength="20" value="{{ old('genero', $perfil['genero'] ?? '') }}"><x-error-campo name="genero"/></label>
    </div></fieldset>
    <fieldset class="perfil-seccion"><legend><span>02</span>Datos de contacto</legend><div class="rejilla dos">
        <label for="telefono">Teléfono *<input id="telefono" type="tel" name="telefono" maxlength="30" value="{{ old('telefono', $perfil['telefono'] ?? '') }}" autocomplete="tel"><x-error-campo name="telefono"/></label>
        <label for="direccion">Dirección *<input id="direccion" name="direccion" maxlength="200" value="{{ old('direccion', $perfil['direccion'] ?? '') }}" autocomplete="street-address"><x-error-campo name="direccion"/></label>
        <label for="emailPerfil">Correo de tu cuenta<input id="emailPerfil" type="email" value="{{ $perfil['email'] ?? session('usuario.email') }}" readonly><small>Para modificar el correo, contacta al personal.</small></label>
    </div></fieldset>
    <fieldset class="perfil-seccion"><legend><span>03</span>Documentación</legend><div class="rejilla dos">
        <label for="tipoDocumento">Tipo de documento *<input id="tipoDocumento" name="tipoDocumento" maxlength="20" value="{{ old('tipoDocumento', $perfil['tipoDocumento'] ?? '') }}" placeholder="Por ejemplo: pasaporte"><x-error-campo name="tipoDocumento"/></label>
        <label for="numeroDocumento">Número de documento *<input id="numeroDocumento" name="numeroDocumento" maxlength="30" value="{{ old('numeroDocumento', $perfil['numeroDocumento'] ?? '') }}"><x-error-campo name="numeroDocumento"/></label>
    </div></fieldset>
    <div class="perfil-acciones"><p>Puedes guardar tus avances.<br>Completa los campos con * para enviar tu pedido de viaje.</p><button type="submit" class="boton" data-submitting-label="Guardando cambios…">Guardar mi perfil ↗</button></div>
</form>
@endsection
