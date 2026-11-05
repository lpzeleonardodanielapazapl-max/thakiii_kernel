@extends('layouts.admin')
@section('titulo', 'Empleados')
@section('contenido')
    <x-record-list title="Empleados" description="El equipo que acompaña cada viaje: guías, asesores y conductores." :records="$empleados" record-key="codEmpleado"
        :columns="[
        ['key' => 'codEmpleado', 'label' => 'Código'],
        ['key' => 'codPersona', 'label' => 'Persona', 'route' => 'personas.index'],
        ['key' => 'codCargo', 'label' => 'Cargo'],
        ['key' => 'salario', 'label' => 'Salario'],
        ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'],
        ['key' => 'funciones', 'label' => 'Funciones', 'type' => 'functions']
        ]" :related="['personas.index' => 'Ver personas', 'usuarios.index' => 'Ver usuarios']"/>
@endsection
