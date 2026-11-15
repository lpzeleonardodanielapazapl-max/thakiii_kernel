@extends('layouts.admin')
@section('titulo', 'Gestión')
@section('clase-pagina', 'gestion-operaciones')
@section('contenido')
<header class="operaciones-cabecera"><div><p class="sobre-titulo">HERRAMIENTAS DEL EQUIPO</p><h1>Gestión</h1><p class="descripcion">Organización, accesos y análisis, cada uno en su lugar.</p></div></header>
<div class="gestion-secciones">
    @foreach([
        ['titulo' => 'Personas y equipo', 'descripcion' => 'Las fichas y la organización del personal.', 'enlaces' => [
            ['personas.index', 'Personas', 'Datos personales y vínculos con empleados o clientes.', 'users'],
            ['empleados.index', 'Empleados', 'Cargos, funciones y datos laborales.', 'briefcase'],
            ['cargos.index', 'Cargos', 'Trabajos y responsabilidades del equipo.', 'briefcase'],
            ['departamentos.index', 'Departamentos', 'Áreas de trabajo y organización.', 'grid'],
        ]],
        ['titulo' => 'Alojamientos y movilidad', 'descripcion' => 'Catálogos del equipo para preparar las opciones del viaje.', 'enlaces' => [
            ['hoteles.index', 'Hoteles y alojamientos', 'Registrar, editar y desactivar alojamientos.', 'mountain'],
            ['habitaciones.index', 'Habitaciones', 'Capacidad, camas y detalles de cada hotel.', 'grid'],
            ['tarifas-hotel.index', 'Tarifas de alojamiento', 'Precios por noche, temporadas y desayuno.', 'calendar'],
            ['servicios-transporte.index', 'Servicios de traslado', 'Rutas, tipos de servicio y precios base.', 'briefcase'],
            ['vehiculos.index', 'Vehículos', 'Flota, capacidades y estado de los vehículos.', 'box'],
            ['conductores.index', 'Conductores', 'Personal vinculado y vigencia de las licencias.', 'users'],
        ]],
        ['titulo' => 'Usuarios y accesos', 'descripcion' => session('usuario.rol') === 'ADMINISTRATIVO' ? 'Consulta de cuentas y roles. Los cambios corresponden al administrador.' : 'Administración de las cuentas y los roles del sistema.', 'enlaces' => [
            ['usuarios.index', 'Usuarios', 'Cuentas, roles asignados y estado del acceso.', 'users'],
            ['roles.index', 'Roles', 'ADMIN, ADMINISTRATIVO y CLIENTE.', 'shield'],
        ]],
    ] as $grupo)
    <section class="gestion-seccion" aria-labelledby="grupo-{{ $loop->index }}">
        <div class="gestion-seccion-titulo"><h2 id="grupo-{{ $loop->index }}">{{ $grupo['titulo'] }}</h2><p>{{ $grupo['descripcion'] }}</p></div>
        <div class="gestion-enlaces">@foreach($grupo['enlaces'] as [$ruta, $titulo, $detalle, $icono])<a class="gestion-enlace" href="{{ route($ruta) }}"><span class="gestion-icono"><x-icon :name="$icono"/></span><div><h3>{{ $titulo }}</h3><p>{{ $detalle }}</p></div><x-icon name="arrow"/></a>@endforeach</div>
    </section>
    @endforeach
</div>
@endsection
