@extends('layouts.publico')
@section('titulo', 'Servicio no disponible')
@section('contenido')<section class="seccion lectura"><span class="etiqueta">VOLVAMOS A INTENTARLO</span><h1>No pudimos conectar con tu espacio.</h1><p>El servicio de viajes no responde en este momento. Tus datos no se han enviado. Verifica que Quarkus esté iniciado e inténtalo de nuevo.</p><a class="boton" href="{{ url()->current() }}">Volver a intentar</a><a class="enlace" href="{{ route('publico.inicio') }}">Ir al inicio</a></section>@endsection
