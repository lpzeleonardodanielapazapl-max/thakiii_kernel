@extends('layouts.admin')
@section('titulo', 'Consulta no disponible')
@section('contenido')
    <section class="panel estado-error">
        <span class="icono-destacado"><x-icon name="refresh"/></span>
        <p class="sobre-titulo">HAGAMOS UNA PAUSA</p>
        <h1>No pudimos cargar esta sección.</h1>
        <p>La consulta no está disponible en este momento. Comprueba que Quarkus y MySQL estén funcionando y vuelve a intentarlo.</p>
        <div class="acciones-pagina"><a class="boton" href="{{ url()->current() }}"><x-icon name="refresh"/> Volver a intentar</a><a class="boton boton-secundario" href="{{ route('admin.index') }}">Ir al panel</a></div>
    </section>
@endsection
