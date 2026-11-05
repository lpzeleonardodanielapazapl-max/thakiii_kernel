@extends('layouts.admin')
@section('titulo', 'Personas')
@section('contenido')
    <x-record-list title="Personas" description="Un solo registro para cada persona. La base de tu equipo y tus clientes." :records="$personas" record-key="codPersona"
        :columns="[
        ['key' => 'codPersona', 'label' => 'Código'],
        ['key' => 'nombres', 'label' => 'Nombres'],
        ['key' => 'apellidos', 'label' => 'Apellidos'],
        ['key' => 'email', 'label' => 'Correo'],
        ['key' => 'telefono', 'label' => 'Teléfono']
        ]" :related="['empleados.index' => 'Ver empleados', 'clientes.index' => 'Ver clientes']"/>
@endsection
