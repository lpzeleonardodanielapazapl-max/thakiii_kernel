@extends(in_array(session('usuario.rol'), ['ADMIN','ADMINISTRATIVO'], true) ? 'layouts.admin' : 'layouts.publico')
@section('titulo', 'Acceso restringido')
@section('contenido')<section class="seccion lectura formulario-panel"><span class="etiqueta">TU CUENTA</span><h1>Este apartado requiere otro acceso.</h1><p>Continúa en el espacio correspondiente a tu cuenta.</p><a class="boton" href="{{ route(session('usuario.rol') === 'CLIENTE' ? 'portal.inicio' : 'admin.index') }}">Ir a mi espacio</a></section>@endsection
