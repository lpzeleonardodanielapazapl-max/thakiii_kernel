@extends('layouts.admin')
@section('titulo', 'Clasificación ABC')
@section('contenido')
@php
        $formato = fn ($valor) => number_format($valor, 2, ',', '.');
        $productos = collect($abc['productos']);
    @endphp

    <x-page-header title="Cada equipo cuenta." description="Descubre dónde se concentra el valor de tu inventario y organiza tus prioridades." eyebrow="ANÁLISIS · CLASIFICACIÓN ABC">
        <a class="boton boton-secundario" href="{{ route('inventario.index') }}"><x-icon name="back"/> Inventario</a>
        <button class="boton" type="button" data-print><x-icon name="print"/> Imprimir / PDF</button>
    </x-page-header>

    <section class="panel">
        <h2>Valor de referencia analizado</h2>

        <span class="valor-grande">
            Bs {{ $formato($abc['valorTotal']) }}
        </span>

        <p>
            {{ $productos->count() }} productos con unidades y precio
            válido en bolivianos.
        </p>

        <small>
            Valor = unidades sin baja × último precio registrado en BOB.
            No representa depreciación contable ni disponibilidad inmediata.
            Los registros [DEMO] utilizan precios simulados.
        </small>
    </section>

    <div class="resumen-abc">
        <section class="panel">
            <span class="badge A">A · Mayor prioridad</span>
            <span class="valor-grande">
                {{ $productos->where('clasificacion', 'A')->count() }}
            </span>
            <p>
                Concentran aproximadamente el primer 80 % del valor.
                Conviene un control más frecuente y detallado.
            </p>
        </section>

        <section class="panel">
            <span class="badge B">B · Prioridad intermedia</span>
            <span class="valor-grande">
                {{ $productos->where('clasificacion', 'B')->count() }}
            </span>
            <p>
                Amplían la cobertura hasta aproximadamente el 95 %.
                Requieren seguimiento periódico.
            </p>
        </section>

        <section class="panel">
            <span class="badge C">C · Menor concentración de valor</span>
            <span class="valor-grande">
                {{ $productos->where('clasificacion', 'C')->count() }}
            </span>
            <p>
                Representan el valor restante. Mantienen su control
                habitual, aunque su participación económica sea menor.
            </p>
        </section>
    </div>

    <section class="panel" data-table data-print-target="tabla-impresion">
        <h2>Productos ordenados por valor</h2>
        <x-table-tools label="Buscar producto o código" :filters="['A' => 'Clase A', 'B' => 'Clase B', 'C' => 'Clase C']"/>

        <div class="tabla-scroll">
            <table class="tabla-inventario">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Unidades</th>
                        <th>Precio BOB</th>
                        <th>Valor BOB</th>
                        <th>% del total</th>
                        <th>% acumulado</th>
                        <th>Clase</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($abc['productos'] as $p)
                        <tr data-row data-print-key="{{ $loop->index }}" data-tags="{{ $p['clasificacion'] }}">
                            <td>
                                <strong>{{ $p['nombre'] }}</strong><br>
                                <small>
                                    {{ $p['codigo'] }}<br>
                                    Precio registrado:
                                    {{ $p['fechaPrecio'] }}
                                </small>
                            </td>

                            <td>{{ $p['unidades'] }}</td>

                            <td class="derecha">
                                {{ $formato($p['precioUnitario']) }}
                            </td>

                            <td class="derecha">
                                {{ $formato($p['valorTotal']) }}
                            </td>

                            <td>{{ $formato($p['porcentaje']) }} %</td>

                            <td>
                                {{ $formato($p['porcentajeAcumulado']) }} %
                            </td>

                            <td>
                                <span class="badge {{ $p['clasificacion'] }}">
                                    {{ $p['clasificacion'] }}
                                </span>
                            </td>

                            <td>
                                <a class="accion-enlace" href="{{ route('inventario.unidades', ['codigo' => $p['codigo']]) }}">Ver unidades ↗</a><br>
                                <a class="accion-enlace" href="{{ route('inventario.analizar', [
                                    'codigo' => $p['codigo']
                                ]) }}">
                                    Planificar compra
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                No hay productos con unidades y precios
                                válidos para realizar la clasificación.
                            </td>
                        </tr>
                    @endforelse
                    <tr data-no-results hidden><td colspan="8" class="estado-vacio">No encontramos coincidencias.</td></tr>
                </tbody>
            </table>
        </div>

        <p>
            <small>
                Regla utilizada: el producto que permite alcanzar o superar
                el 80 % permanece en A; el que alcanza o supera el 95 %
                permanece en B. Por eso los límites pueden sobrepasarse.
            </small>
        </p>
    </section>

    @if (count($abc['sinPrecio']) > 0)
        <section class="panel">
            <h2>Pendientes de valoración</h2>

            <p>
                Estos productos no se incluyeron en los porcentajes
                porque no tienen un precio positivo registrado en BOB:
            </p>

            <ul>
                @foreach ($abc['sinPrecio'] as $nombre)
                    <li>{{ $nombre }}</li>
                @endforeach
            </ul>

            <p>
                La clasificación corresponde únicamente a los productos
                valorados, no a todo el inventario de la empresa.
            </p>
        </section>
    @endif

    @if (count($abc['sinUnidades']) > 0)
        <section class="panel">
            <h2>Productos sin unidades sin baja</h2>

            <ul>
                @foreach ($abc['sinUnidades'] as $nombre)
                    <li>{{ $nombre }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="panel">
        <h2>¿Cómo utilizar este resultado?</h2>

        <p>
            Revisa primero los productos A para controlar pérdidas,
            daños y necesidades de reposición. Después utiliza
            «Planificar compra» cuando exista una necesidad de adquisición.
        </p>

        <p>
            <strong>ABC no mide la importancia para la seguridad.</strong>
            Un artículo de bajo precio puede ser indispensable en montaña,
            aunque pertenezca al grupo C.
        </p>
    </section>
    <x-print-report title="Clasificación ABC del inventario" subtitle="Valoración de unidades sin baja · Moneda: bolivianos (BOB)" :landscape="true">
        <div class="reporte-resumen"><p><strong>Valor total del análisis:</strong> {{ $formato($abc['valorTotal']) }} BOB · <strong>Productos valorados:</strong> {{ $productos->count() }}</p><p>Clase A: {{ $productos->where('clasificacion', 'A')->count() }} · Clase B: {{ $productos->where('clasificacion', 'B')->count() }} · Clase C: {{ $productos->where('clasificacion', 'C')->count() }}</p></div>
        <p class="reporte-alcance"><span data-report-count>{{ $productos->count() }} de {{ $productos->count() }} registros</span> · <span data-report-filters>Sin filtros adicionales.</span></p>
        <table id="tabla-impresion" class="reporte-tabla reporte-abc"><thead><tr><th scope="col">Producto / código</th><th scope="col">Unidades</th><th scope="col">Precio unitario BOB</th><th scope="col">Valor BOB</th><th scope="col">% del total</th><th scope="col">% acumulado</th><th scope="col">Fecha del precio</th><th scope="col">Clase</th></tr></thead><tbody>
            @foreach ($abc['productos'] as $p)<tr data-print-key="{{ $loop->index }}"><td><strong>{{ $p['nombre'] }}</strong><small>{{ $p['codigo'] }}</small></td><td class="numero-informe">{{ $p['unidades'] }}</td><td class="numero-informe">{{ $formato($p['precioUnitario']) }}</td><td class="numero-informe">{{ $formato($p['valorTotal']) }}</td><td class="numero-informe">{{ $formato($p['porcentaje']) }} %</td><td class="numero-informe">{{ $formato($p['porcentajeAcumulado']) }} %</td><td><x-print-value :value="$p['fechaPrecio']" type="date"/></td><td>{{ $p['clasificacion'] }}</td></tr>@endforeach
            <tr data-report-empty @if ($productos->count()) hidden @endif><td colspan="8">No hay productos valorados para el alcance seleccionado.</td></tr>
        </tbody></table>
        <p class="reporte-nota">El total y los porcentajes corresponden al análisis completo, incluso si el listado está filtrado. Valor = unidades sin baja × último precio válido en BOB. No equivale a depreciación ni disponibilidad. Los precios [DEMO] son simulados.</p>
        <p class="reporte-nota">Criterio: el producto que alcanza o supera el 80 % permanece en A; el que alcanza o supera el 95 % permanece en B. ABC mide concentración de valor, no importancia para la seguridad.</p>
        @if (count($abc['sinPrecio']))<h2>Excluidos del análisis: sin precio válido ({{ count($abc['sinPrecio']) }})</h2><ul>@foreach ($abc['sinPrecio'] as $nombre)<li>{{ $nombre }}</li>@endforeach</ul>@endif
        @if (count($abc['sinUnidades']))<h2>Excluidos del análisis: sin unidades sin baja ({{ count($abc['sinUnidades']) }})</h2><ul>@foreach ($abc['sinUnidades'] as $nombre)<li>{{ $nombre }}</li>@endforeach</ul>@endif
    </x-print-report>
@endsection
