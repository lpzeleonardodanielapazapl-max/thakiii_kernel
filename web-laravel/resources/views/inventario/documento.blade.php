@extends('layouts.admin')
@section('titulo',$documento['tipo'].' · '.$documento['numero'])
@push('styles')<link rel="stylesheet" href="{{ asset('css/inventario-operaciones.css') }}">@endpush
@section('contenido')
<x-page-header :title="$documento['tipo']==='NI' ? 'Nota de ingreso' : 'Nota de salida'" :description="$documento['numero']" eyebrow="COMPROBANTE DE ALMACÉN"><a class="boton boton-secundario" href="{{ route('inventario.operaciones',['ver'=>'documentos']) }}">Ver documentos</a><button type="button" class="boton" data-print><x-icon name="print"/> Imprimir / guardar PDF</button></x-page-header>
@if(session('exito'))<div class="aviso" role="status">{{ session('exito') }}</div>@endif
<section class="panel inv-documento"><h2>{{ $documento['tipo'] }} · {{ $documento['numero'] }}</h2>@include('inventario.documento-contenido',['impresion'=>false])</section>
<x-print-report :title="$documento['tipo']==='NI' ? 'Nota de ingreso' : 'Nota de salida'" :reference="$documento['numero']" subtitle="Control de material · Almacenes">
@include('inventario.documento-contenido',['impresion'=>true])
</x-print-report>
@endsection
