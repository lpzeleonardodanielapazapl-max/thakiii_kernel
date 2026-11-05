@extends('layouts.admin')
@section('titulo', $meta['title'])
@section('contenido')
    @if (session('exito'))<div class="aviso" role="status">{{ session('exito') }}</div>@endif
    <x-record-list :title="$meta['title']" :description="$meta['description']" :records="$registros" :columns="$meta['columns']" :record-key="$meta['key']" :related="$meta['related']" :module="$meta['modulo']" :can-create="$meta['canCreate'] ?? true"/>
@endsection
