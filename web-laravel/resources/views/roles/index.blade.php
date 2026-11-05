@extends('layouts.admin')
@section('titulo', 'Roles')
@section('contenido')
    <x-record-list title="Roles" description="Consulta los roles del sistema y las responsabilidades de cada uno." :records="$roles" record-key="codRol"
        :columns="[
        ['key' => 'codRol', 'label' => 'Código'],
        ['key' => 'desRol', 'label' => 'Nombre'],
        ['key' => 'descripcion', 'label' => 'Descripción']
        ]" :related="['usuarios.index' => 'Ver usuarios']"/>
@endsection
