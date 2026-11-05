@extends('layouts.admin')
@section('titulo', 'Reposición · EOQ')
@section('clase-pagina', 'pagina-eoq')
@section('contenido')
@php
        $campos = [
            'demandaAnual' => [
                '¿Cuántas unidades prevés comprar durante un año?',
                'Cuenta las unidades para reemplazar bajas o ampliar el equipo. No cuentes préstamos que luego regresan.',
                'Ejemplo: 12',
                1,
                1,
                ''
            ],
            'costoUnitario' => [
                '¿Cuánto cuesta comprar una unidad?',
                'Precio de referencia de una sola unidad, expresado en bolivianos.',
                'Ejemplo: 900',
                0,
                '0.01',
                ''
            ],
            'costoPedido' => [
                '¿Cuánto cuesta realizar cada pedido?',
                'Gastos fijos por compra, como transporte o trámites. No incluyas aquí el precio de los productos ni lo cuentes dos veces.',
                'Ejemplo: 100',
                '0.01',
                '0.01',
                ''
            ],
            'costoMantenimientoAnual' => [
                '¿Cuánto cuesta mantener una unidad durante un año?',
                'Costo anual de almacenamiento, seguro y conservación atribuible a una unidad. No es el costo por pedido.',
                'Ejemplo: 60',
                '0.01',
                '0.01',
                ''
            ],
            'plazoEntregaDias' => [
                '¿Cuántos días tarda en llegar un pedido?',
                'Tiempo desde que solicitas la compra hasta que recibes el producto.',
                'Ejemplo: 15',
                0,
                1,
                ''
            ],
            'diasAnuales' => [
                '¿Cuántos días considerarás en el año?',
                'Usa 360 para un año comercial o 365 para un año calendario. El plazo de entrega debe usar la misma base de días.',
                'Ejemplo: 360',
                1,
                1,
                360
            ],
        ];

        $formato = fn ($valor) => number_format($valor, 2, ',', '.');
    @endphp

    <x-page-header :title="isset($producto) ? 'Planifica la próxima compra.' : 'Una compra bien pensada.'" description="Estima cantidades, costos y punto de reorden. Tú decides cuándo comprar." eyebrow="ANÁLISIS · REPOSICIÓN EOQ">
        <a class="boton boton-secundario" href="{{ route('inventario.index') }}"><x-icon name="back"/> Inventario</a>
        @isset($producto)<a class="boton boton-secundario" href="{{ route('inventario.unidades', ['codigo' => $producto['codigo']]) }}">Ver unidades</a>@endisset
        <a class="boton boton-secundario" href="{{ route('inventario.abc') }}">Ver ABC</a>
    </x-page-header>

    @isset($resultado)
        <div class="banda-contexto no-imprimir">
            <span class="icono-destacado"><x-icon name="calculator"/></span>
            <div><strong>{{ $resultado['loteRecomendado'] }} {{ $resultado['loteRecomendado'] == 1 ? 'unidad por pedido' : 'unidades por pedido' }}</strong><p>La recomendación está lista. Revisa los costos y los supuestos antes de decidir.</p></div>
            <a class="accion-enlace" href="#recomendacion">Ver resultado <x-icon name="arrow"/></a>
            <button class="boton boton-secundario" type="button" data-print><x-icon name="print"/> Imprimir informe / Guardar PDF</button>
        </div>
    @endisset

    @isset($producto)
        <section class="panel">
            <h2>{{ $producto['nombre'] }}</h2>

            <p>
                Código: <strong>{{ $producto['codigo'] }}</strong>
                · Categoría: {{ $producto['categoria'] }}
            </p>

            <p>
                <strong>{{ $producto['unidadesRegistradas'] }}</strong>
                unidades registradas ·
                <strong>{{ $producto['unidadesRequierenArreglo'] }}</strong>
                pendientes de arreglo ·
                <strong>{{ $producto['unidadesConBaja'] }}</strong>
                con baja registrada.
            </p>

            <p class="ayuda">
                Las unidades registradas no equivalen necesariamente a
                unidades disponibles. Este cálculo todavía no comprueba
                salidas pendientes ni pedidos en camino.
            </p>
        </section>
    @endisset

    <section class="panel formulario-panel no-imprimir">
        <h2>1. Indica tu necesidad y los costos</h2>

        <p>
            Todos los importes deben estar en
            <strong>bolivianos (BOB)</strong>.
            Puedes usar estimaciones identificadas como tales
            para comparar escenarios.
        </p>

        <p>
            <strong>Ejemplo:</strong>
            tener 13 botellones y prestar 10 no significa que debas comprar 10.
            Aquí debes indicar cuántos necesitarás adquirir durante el año
            por reemplazo o crecimiento.
        </p>

        @if ($errors->any())
            <div class="error" role="alert">
                <p><strong>Revisa los siguientes datos:</strong></p>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ isset($producto)
                ? route('inventario.calcular', ['codigo' => $producto['codigo']])
                : url('/investigacion-operativa/eoq') }}"
        >
            @csrf

            <div class="formulario-grid">
                @foreach ($campos as $nombre => $campo)
                    <div>
                        <label for="{{ $nombre }}">
                            {{ $campo[0] }}
                        </label>

                        <input
                            id="{{ $nombre }}"
                            name="{{ $nombre }}"
                            type="number"
                            min="{{ $campo[3] }}"
                            step="{{ $campo[4] }}"
                            placeholder="{{ $campo[2] }}"
                            value="{{ old($nombre, $datos[$nombre] ?? $campo[5]) }}"
                            aria-describedby="ayuda-{{ $nombre }}"
                            required
                        >

                        <small class="ayuda" id="ayuda-{{ $nombre }}">
                            {{ $campo[1] }}
                        </small>
                    </div>
                @endforeach
            </div>

            <button class="boton" type="submit">
                Obtener recomendación de compra
            </button>
        </form>
    </section>

    @isset($resultado)
        <section class="panel" id="recomendacion">
            <h2>2. Recomendación para los datos ingresados</h2>

            <p>Cantidad recomendada por pedido:</p>

            <div class="valor-grande">
                {{ $resultado['loteRecomendado'] }} unidades
            </div>

            <p>
                Para una necesidad anual de
                <strong>{{ $formato($datos['demandaAnual']) }} unidades</strong>,
                conviene organizar compras de
                <strong>{{ $resultado['loteRecomendado'] }} unidades</strong>
                bajo los supuestos del modelo.
            </p>

            <p>
                Esto representa aproximadamente
                <strong>
                    {{ $formato($resultado['pedidosAnuales']) }}
                    pedidos por año
                </strong>,
                con un intervalo promedio de
                <strong>
                    {{ $formato($resultado['diasEntrePedidos']) }} días.
                </strong>
            </p>

            <p class="ayuda">
                Es una frecuencia promedio del modelo.
                El calendario real y el último pedido pueden ajustarse
                a las necesidades efectivas.
            </p>

            <p>
                <strong>No significa que debas comprar esa cantidad hoy.</strong>
                Antes debes revisar existencias utilizables, equipos que
                regresarán y compras pendientes.
            </p>
        </section>

        <section class="panel">
            <h2>3. ¿Cuándo revisar una nueva compra?</h2>

            <p>
                El punto de reorden calculado es
                <strong>
                    {{ $formato($resultado['puntoReorden']) }} unidades.
                </strong>
            </p>

            <p>
                Para unidades enteras, una referencia conservadora es
                <strong>
                    {{ (int) ceil($resultado['puntoReorden']) }} {{ (int) ceil($resultado['puntoReorden']) === 1 ? 'unidad' : 'unidades' }}.
                </strong>
            </p>

            <p>
                Este nivel se compara con la
                <strong>posición de inventario</strong>:
                existencias utilizables + compras pendientes de recepción
                − demanda pendiente de atender.
            </p>

            <p class="ayuda">
                El sistema aún no realiza esta comparación automáticamente.
                Para equipo reutilizable, las salidas temporales no representan
                consumo definitivo.
            </p>

            @if (
                $datos['plazoEntregaDias'] >
                $resultado['diasEntrePedidos']
            )
                <p>
                    <strong>Atención:</strong>
                    el proveedor tarda más en entregar que el intervalo
                    calculado entre pedidos. Esta política puede requerir
                    varios pedidos simultáneamente en camino.
                </p>
            @endif
        </section>

        <section class="panel">
            <h2>4. Costos estimados por año</h2>

            <div class="tabla-scroll">
                <table class="tabla-inventario">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Importe en BOB</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Compra de las unidades previstas</td>
                            <td>
                                Bs {{ $formato($resultado['costoCompraAnual']) }}
                            </td>
                        </tr>

                        <tr>
                            <td>Realización de pedidos</td>
                            <td>
                                Bs {{ $formato($resultado['costoOrdenarAnual']) }}
                            </td>
                        </tr>

                        <tr>
                            <td>Mantenimiento del inventario</td>
                            <td>
                                Bs {{ $formato($resultado['costoMantenerAnual']) }}
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <strong>Total anual estimado</strong>
                            </td>
                            <td>
                                <strong>
                                    Bs {{ $formato($resultado['costoTotalAnual']) }}
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="ayuda">
                Son estimaciones del escenario. No representan pagos
                realizados ni una orden de compra.
            </p>
        </section>

        <section class="panel">
            <details>
                <summary>
                    <strong>Ver explicación del modelo EOQ</strong>
                </summary>

                <p>
                    EOQ calcula el tamaño de pedido que equilibra
                    el costo de realizar compras y el costo de mantener
                    inventario.
                </p>

                <p>
                    <strong>Fórmula:</strong>
                    Q* = √(2 × D × S / H)
                </p>

                <ul>
                    <li>D: necesidad anual de adquisición.</li>
                    <li>S: costo fijo por pedido.</li>
                    <li>H: costo anual de mantener una unidad.</li>
                </ul>

                <p>
                    Resultado teórico:
                    <strong>
                        {{ $formato($resultado['loteTeorico']) }} unidades.
                    </strong>
                    Se comparan los enteros vecinos para recomendar
                    una cantidad de unidades completas.
                </p>

                <p>
                    Supuestos: demanda constante, costos constantes,
                    plazo de entrega conocido, recepción del lote completo
                    y ausencia de faltantes.
                </p>
            </details>
        </section>
    @endisset
@isset($resultado)
    @php
        $monedaInforme = 'BOB';

        $numeroInforme = fn ($valor) =>
            number_format($valor, 2, ',', '.');
    @endphp

    <article id="informe-impresion">
        <h1>THAKI VOYAGE</h1>
        <h2>Informe de planificación de reposición</h2>

        <p>
            <strong>Producto:</strong>
            {{ $producto['nombre'] ?? 'Escenario académico sin producto vinculado' }}

            @isset($producto)
                <br>
                <strong>Código:</strong> {{ $producto['codigo'] }}
            @endisset

            <br>
            <strong>Fecha de emisión:</strong>
            {{ now()->timezone('America/La_Paz')->format('d/m/Y H:i') }}

            <br>
            <strong>Moneda:</strong> {{ $monedaInforme }}
        </p>

        <h3>Datos utilizados</h3>

        <table class="tabla-inventario">
            <tbody>
                <tr>
                    <td>Necesidad anual de adquisición</td>
                    <td>
                        {{ $numeroInforme($datos['demandaAnual']) }}
                        unidades/año
                    </td>
                </tr>
                <tr>
                    <td>Precio unitario</td>
                    <td>
                        {{ $numeroInforme($datos['costoUnitario']) }}
                        {{ $monedaInforme }}/unidad
                    </td>
                </tr>
                <tr>
                    <td>Costo por pedido</td>
                    <td>
                        {{ $numeroInforme($datos['costoPedido']) }}
                        {{ $monedaInforme }}/pedido
                    </td>
                </tr>
                <tr>
                    <td>Costo de mantener una unidad por año</td>
                    <td>
                        {{ $numeroInforme($datos['costoMantenimientoAnual']) }}
                        {{ $monedaInforme }}/unidad/año
                    </td>
                </tr>
                <tr>
                    <td>Plazo de entrega / Base anual</td>
                    <td>
                        {{ $datos['plazoEntregaDias'] }} días /
                        {{ $datos['diasAnuales'] }} días
                    </td>
                </tr>
            </tbody>
        </table>

        <h3>Resultados</h3>

        <table class="tabla-inventario">
            <tbody>
                <tr>
                    <td>EOQ teórico</td>
                    <td>
                        {{ $numeroInforme($resultado['loteTeorico']) }}
                        unidades
                    </td>
                </tr>
                <tr>
                    <td><strong>Lote recomendado por pedido</strong></td>
                    <td>
                        <strong>
                            {{ $resultado['loteRecomendado'] }} unidades
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td>Pedidos anuales / Intervalo promedio</td>
                    <td>
                        {{ $numeroInforme($resultado['pedidosAnuales']) }}
                        pedidos/año /
                        {{ $numeroInforme($resultado['diasEntrePedidos']) }}
                        días
                    </td>
                </tr>
                <tr>
                    <td>Punto de reorden teórico</td>
                    <td>
                        {{ $numeroInforme($resultado['puntoReorden']) }}
                        unidades
                    </td>
                </tr>
                <tr>
                    <td>Costo anual de compra</td>
                    <td>
                        {{ $numeroInforme($resultado['costoCompraAnual']) }}
                        {{ $monedaInforme }}
                    </td>
                </tr>
                <tr>
                    <td>Costo anual de ordenar / mantener</td>
                    <td>
                        {{ $numeroInforme($resultado['costoOrdenarAnual']) }}
                        /
                        {{ $numeroInforme($resultado['costoMantenerAnual']) }}
                        {{ $monedaInforme }}
                    </td>
                </tr>
                <tr>
                    <td><strong>Costo total anual estimado</strong></td>
                    <td>
                        <strong>
                            {{ $numeroInforme($resultado['costoTotalAnual']) }}
                            {{ $monedaInforme }}
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <h3>Recomendación</h3>

        <p>
            Bajo los parámetros ingresados, se recomienda un lote de
            <strong>{{ $resultado['loteRecomendado'] }} unidades por pedido</strong>.
            La decisión de comprar debe considerar las existencias utilizables,
            los equipos que regresarán y las compras pendientes.
        </p>

        <p class="nota-informe">
            Modelo EOQ clásico: demanda y costos constantes, plazo conocido,
            recepción del lote completo y ausencia de faltantes.
            Este informe no constituye una orden de compra.
        </p>
    </article>
@endisset
@endsection
