@props(['label' => 'Buscar en este listado', 'filters' => []])
<div class="table-tools no-imprimir">
    <label class="buscador"><x-icon name="search"/><span class="solo-lectores">{{ $label }}</span><input type="search" data-search placeholder="{{ $label }}" autocomplete="off"></label>
    @if ($filters)
        <label class="selector-filtro"><span class="solo-lectores">Filtrar registros</span><select data-filter><option value="">Todos los registros</option>@foreach ($filters as $valor => $texto)<option value="{{ $valor }}">{{ $texto }}</option>@endforeach</select></label>
    @endif
    <button type="button" class="boton boton-texto" data-reset>Limpiar</button>
    <span class="contador-resultados" data-count role="status" aria-live="polite"></span>
</div>
