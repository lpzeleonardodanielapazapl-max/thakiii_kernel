@extends('layouts.admin')
@section('titulo', 'Clientes')
@section('contenido')
    <x-record-list title="Clientes" description="Las personas que confían en Thaki para su próxima aventura." :records="$clientes" record-key="codCliente"
        :columns="[
        ['key' => 'codCliente', 'label' => 'Código'],
        ['key' => 'codPersona', 'label' => 'Persona', 'route' => 'personas.index'],
        ['key' => 'codigoCliente', 'label' => 'Referencia'],
        ['key' => 'estado', 'label' => 'Estado', 'type' => 'status']
        ]" :related="['personas.index' => 'Ver personas', 'usuarios.index' => 'Ver usuarios']"/>
@endsection
