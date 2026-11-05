@extends('layouts.admin')
@section('titulo', 'Usuarios')
@section('contenido')
    <x-record-list title="Usuarios" description="Consulta las cuentas del sistema y el rol asignado a cada una." :records="$usuarios" record-key="idUsuario"
        :columns="[
        ['key' => 'idUsuario', 'label' => 'ID'],
        ['key' => 'email', 'label' => 'Correo'],
        ['key' => 'codRol', 'label' => 'Rol', 'route' => 'roles.index'],
        ['key' => 'activo', 'label' => 'Acceso', 'type' => 'boolean']
        ]" :related="['roles.index' => 'Ver roles', 'empleados.index' => 'Ver empleados', 'clientes.index' => 'Ver clientes']"/>
@endsection
