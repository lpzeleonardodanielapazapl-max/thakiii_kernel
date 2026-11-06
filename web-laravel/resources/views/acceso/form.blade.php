@extends('layouts.publico')
@php($titulos = ['ingresar' => 'Qué bueno verte de nuevo.', 'registrar' => 'Tu viaje comienza aquí.', 'inicializar' => 'Prepara la administración.'])
@section('titulo', $titulos[$modo])
@section('contenido')
<section class="acceso"><div class="acceso-foto" style="--foto:url('{{ asset('images/thaki/la-paz.jpg') }}')"><span class="etiqueta clara">THAKI VOYAGE</span><h2>Un lugar para<br><em>tu próximo camino.</em></h2></div><div class="acceso-formulario"><span class="etiqueta">{{ $modo === 'inicializar' ? 'CONFIGURACIÓN INICIAL · SOLO LOCAL' : 'TU ESPACIO PRIVADO' }}</span><h1>{{ $titulos[$modo] }}</h1><p>{{ $modo === 'ingresar' ? 'Ingresa con tu correo y contraseña.' : ($modo === 'registrar' ? 'Crea tu cuenta de cliente para diseñar tu viaje y conversar con tu asesor.' : 'Crea la primera cuenta ADMIN. Esta opción se cierra al existir un administrador.') }}</p>
@include('components.avisos-publicos')
<form method="POST" action="{{ route('acceso.'.($modo === 'ingresar' ? 'autenticar' : $modo)) }}" class="formulario">@csrf
@if($modo !== 'ingresar')<label>{{ $modo === 'inicializar' ? 'Nombre de usuario del administrador' : 'Nombres' }}<input name="nombres" value="{{ old('nombres') }}" maxlength="{{ $modo === 'inicializar' ? 60 : 100 }}" autocomplete="given-name" required></label>@endif
@if($modo === 'registrar')<label>Apellidos<input name="apellidos" value="{{ old('apellidos') }}" maxlength="100" autocomplete="family-name" required></label>@endif
<label>Correo electrónico<input name="email" type="email" value="{{ old('email') }}" maxlength="150" autocomplete="username" required></label>
<label>Contraseña<input name="password" type="password" {{ $modo !== 'ingresar' ? 'minlength=8' : '' }} maxlength="255" autocomplete="{{ $modo === 'ingresar' ? 'current-password' : 'new-password' }}" required></label>
@if($modo !== 'ingresar')<small>Usa al menos 8 caracteres.</small><label>Repite tu contraseña<input name="password_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required></label>@endif
<button class="boton" type="submit">{{ $modo === 'ingresar' ? 'Ingresar' : ($modo === 'registrar' ? 'Crear mi cuenta' : 'Crear administrador') }} ↗</button></form>
@if($modo === 'ingresar')<p>¿Es tu primera visita? <a class="enlace" href="{{ route('acceso.registro') }}">Crea tu cuenta</a></p>@if($inicializable)<div class="aviso">Falta la cuenta del administrador. <a href="{{ route('acceso.configurar') }}">Configurar la primera cuenta ADMIN →</a></div>@endif @else<p>¿Ya tienes cuenta? <a class="enlace" href="{{ route('acceso.ingresar') }}">Ingresar</a></p>@endif
</div></section>
@endsection
