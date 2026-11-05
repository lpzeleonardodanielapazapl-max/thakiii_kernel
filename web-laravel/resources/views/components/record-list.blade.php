@props(['title', 'description', 'records', 'columns', 'recordKey', 'related' => [], 'module' => null, 'canCreate' => true])
<x-page-header :title="$title" :description="$description" eyebrow="PERSONAS Y ACCESOS">
    <a class="boton boton-secundario" href="{{ route('admin.index') }}"><x-icon name="back"/> Panel</a>
    <button class="boton" type="button" data-print><x-icon name="print"/> Imprimir</button>
    @if ($module && $canCreate)<a class="boton boton-navy" href="{{ route($module.'.create') }}"><x-icon name="plus"/> Añadir {{ config('gestion.'.$module.'.singular') }}</a>@endif
</x-page-header>
<div class="enlaces-contexto no-imprimir" aria-label="Secciones relacionadas">
    @foreach ($related as $ruta => $texto)<a href="{{ route($ruta) }}">{{ $texto }} <x-icon name="arrow"/></a>@endforeach
</div>
<section class="panel panel-tabla" data-table>
    <div class="titulo-tabla"><div><span class="sobre-titulo">DIRECTORIO</span><h2>{{ $title }} <span class="total-badge">{{ count($records) }}</span></h2></div><a class="enlace-icono no-imprimir" href="{{ url()->current() }}"><x-icon name="refresh"/> Actualizar</a></div>
    <x-table-tools :label="'Buscar en '.mb_strtolower($title)"/>
    <div class="tabla-scroll">
        <table class="tabla-inventario">
            <caption class="solo-lectores">Listado de {{ mb_strtolower($title) }}</caption>
            <thead><tr>@foreach ($columns as $column)<th scope="col">{{ $column['label'] }}</th>@endforeach @if ($module)<th scope="col" class="no-imprimir">Acciones</th>@endif</tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr data-row id="registro-{{ $record[$recordKey] }}">
                        @foreach ($columns as $column)
                            @php($value = $record[$column['key']] ?? null)
                            <td>
                                @if (($column['type'] ?? '') === 'boolean')
                                    <span @class(['badge', 'badge-verde' => $value, 'badge-gris' => ! $value])>{{ $value ? 'Activo' : 'Inactivo' }}</span>
                                @elseif (($column['type'] ?? '') === 'functions')
                                    @foreach (['esGuia' => 'Guía', 'esAsesor' => 'Asesor', 'esConductor' => 'Conductor'] as $flag => $label)
                                        @if ($record[$flag] ?? false)<span class="badge badge-azul">{{ $label }}</span>@endif
                                    @endforeach
                                @elseif (isset($column['linkModule']) && ($record[$column['source']] ?? null))
                                    <a class="enlace-registro" href="{{ route($column['linkModule'].'.show', ['codigo' => $record[$column['source']]]) }}">{{ $value }} ↗</a>
                                @elseif (isset($column['route']) && $value)
                                    <a class="enlace-registro" href="{{ route($column['route']) }}#registro-{{ rawurlencode($value) }}">{{ $value }} <span aria-hidden="true">↗</span></a>
                                @elseif (($column['type'] ?? '') === 'status')
                                    <span class="badge badge-azul">{{ $value ?? 'Sin definir' }}</span>
                                @else
                                    <span @class(['codigo' => $column['key'] === $recordKey])>{{ $value ?? '—' }}</span>
                                @endif
                            </td>
                        @endforeach
                        @if ($module)<td class="no-imprimir"><div class="acciones-fila"><a class="accion-enlace" href="{{ route($module.'.show', ['codigo' => $record[$recordKey]]) }}">Ver detalles ↗</a><a class="accion-enlace" href="{{ route($module.'.edit', ['codigo' => $record[$recordKey]]) }}">Editar</a>@if (config('gestion.'.$module.'.canDelete', true))<a class="accion-enlace" href="{{ route($module.'.delete', ['codigo' => $record[$recordKey]]) }}">Eliminar</a>@endif</div></td>@endif
                    </tr>
                @empty
                    <tr><td class="estado-vacio" colspan="{{ count($columns) + ($module ? 1 : 0) }}"><x-icon name="users"/><strong>Aún no hay registros</strong><span>Los registros de {{ mb_strtolower($title) }} aparecerán aquí.</span>@if ($module && $canCreate)<a class="boton" href="{{ route($module.'.create') }}">Añadir {{ config('gestion.'.$module.'.singular') }}</a>@endif</td></tr>
                @endforelse
                <tr data-no-results hidden><td class="estado-vacio" colspan="{{ count($columns) + ($module ? 1 : 0) }}">No encontramos coincidencias. Prueba con otro nombre o código.</td></tr>
            </tbody>
        </table>
    </div>
</section>
