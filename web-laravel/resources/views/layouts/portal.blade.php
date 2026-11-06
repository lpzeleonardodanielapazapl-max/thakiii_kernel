@extends('layouts.publico')
@section('clase')portal @yield('portal-clase')@endsection
@section('contenido')
<nav class="portal-barra" aria-label="Tu espacio de viaje">@foreach(['portal.inicio' => 'Mi viaje', 'portal.mensajes' => 'Mensajes', 'portal.perfil' => 'Mi perfil', 'portal.notificaciones' => 'Notificaciones'] as $ruta => $texto)<a href="{{ route($ruta) }}" @if(request()->routeIs($ruta)) aria-current="page" @endif>{{ $texto }}</a>@endforeach<form action="{{ route('acceso.salir') }}" method="POST" class="acciones">@csrf<button class="salir" type="submit">Cerrar sesión</button></form></nav>
<div class="portal-cuerpo">@include('components.avisos-publicos', ['compacto' => request()->routeIs('portal.mensajes')])@yield('portal')</div>
@endsection
