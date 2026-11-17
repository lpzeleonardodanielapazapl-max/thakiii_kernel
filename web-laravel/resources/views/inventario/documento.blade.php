@extends('layouts.admin')
@php
    $tituloDocumento = match ($documento['tipo'] ?? '') {
        'NI' => 'Nota de ingreso',
        'NS' => 'Nota de salida',
        'TR' => 'Traslado de almacén',
        default => 'Documento '.$documento['tipo'],
    };
@endphp
@section('titulo',$documento['tipo'].' · '.$documento['numero'])
@push('styles')<link rel="stylesheet" href="{{ asset('css/inventario-operaciones.css') }}">@endpush
@section('contenido')
<x-page-header :title="$tituloDocumento" :description="$documento['numero']" eyebrow="COMPROBANTE DE ALMACÉN"><a class="boton boton-secundario" href="{{ route('inventario.operaciones',['ver'=>'documentos']) }}">Ver documentos</a><button type="button" class="boton" data-print><x-icon name="print"/> Imprimir / guardar PDF</button></x-page-header>
@if(session('exito'))<div class="aviso" role="status">{{ session('exito') }}</div>@endif
<section class="panel inv-documento"><h2>{{ $documento['tipo'] }} · {{ $documento['numero'] }}</h2>@include('inventario.documento-contenido',['impresion'=>false])</section>
<x-print-report :title="$tituloDocumento" :reference="$documento['numero']" subtitle="Control de material · Almacenes">
@include('inventario.documento-contenido',['impresion'=>true])
</x-print-report>
@endsection
