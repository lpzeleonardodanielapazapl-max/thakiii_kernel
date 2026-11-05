<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Planificar reposición · Thaki Voyage</title>

    <style>
        #informe-impresion {
    display: none;
}

@media print {
    @page {
        size: A4;
        margin: 15mm;
    }

    body {
        background: white !important;
        color: black !important;
        font-family: Arial, sans-serif;
        font-size: 10pt;
        line-height: 1.3;
    }

    /* Ocultar todos los elementos normales de la página. */
    body > :not(main),
    main > :not(#informe-impresion) {
        display: none !important;
    }

    main {
        display: block !important;
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0;
    }

    /* Mostrar únicamente el informe. */
    main > #informe-impresion {
        display: block !important;
    }

    #informe-impresion h1 {
        font-size: 18pt;
        color: black;
        margin: 0 0 5px;
    }

    #informe-impresion h2 {
        font-size: 13pt;
        color: black;
        margin: 0 0 15px;
    }

    #informe-impresion h3 {
        font-size: 11pt;
        color: black;
        margin: 15px 0 7px;
        break-after: avoid;
    }

    #informe-impresion table {
        width: 100%;
        border-collapse: collapse;
    }

    #informe-impresion td {
        padding: 6px 8px;
        border: 1px solid #ccc;
        color: black;
    }

    #informe-impresion td:last-child {
        text-align: right;
    }

    #informe-impresion tr {
        break-inside: avoid;
    }

    .nota-informe {
        font-size: 8pt;
    }
}
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f3f5f7;
            color: #24343b;
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }

        main {
            max-width: 1050px;
            margin: 30px auto;
            padding: 20px;
        }

        h1, h2, h3 { color: #164c45; }
        h1 { margin-bottom: 8px; }
        h2 { margin-top: 0; }

        a { color: #164c45; }

        .panel {
            background: white;
            border: 1px solid #dce5e3;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #9eafb3;
            border-radius: 6px;
            font: inherit;
        }

        input:focus {
            outline: 3px solid #c4ded6;
            border-color: #164c45;
        }

        .ayuda {
            display: block;
            color: #53676e;
            font-size: 14px;
            margin-top: 7px;
        }

        button {
            border: 0;
            border-radius: 7px;
            padding: 14px 22px;
            background: #164c45;
            color: white;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
            margin-top: 24px;
        }

        .numero {
            font-size: 36px;
            font-weight: bold;
            color: #164c45;
            margin: 8px 0;
        }

        .error { color: #a51f25; }

        .tabla {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #dce5e3;
        }

        th { background: #edf4f1; }

        @media (max-width: 650px) {
            .grid { grid-template-columns: 1fr; }
            main { padding: 12px; }
            .panel { padding: 18px; }
        }
        .solo-imprimir {
    display: none;
}

    </style>

    <link
    rel="stylesheet"
    href="{{ asset('css/thaki-admin.css') }}?v=1"
>
</head>

<body>
<main>
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
                '0.01',
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

    <a href="{{ route('inventario.index') }}">← Volver al inventario</a>

    <h1>Planificar compras de este producto</h1>

    <p>
        Calcula qué cantidad conviene comprar por pedido
        para cubrir tu necesidad anual de reposición.
    </p>

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

    <section class="panel">
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

            <div class="grid">
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

            <button type="submit">
                Obtener recomendación de compra
            </button>
        </form>
    </section>

    @isset($resultado)
    <div class="no-imprimir">
    <button type="button" onclick="window.print()">
        Imprimir informe / Guardar PDF
    </button>
</div>

<section class="solo-imprimir">
    <h2>Informe de planificación de reposición</h2>

    <p><strong>Empresa:</strong> Thaki Voyage</p>

    @isset($producto)
        <p>
            <strong>Producto:</strong> {{ $producto['nombre'] }}<br>
            <strong>Código:</strong> {{ $producto['codigo'] }}
        </p>
    @endisset

    <p>
        <strong>Fecha de impresión:</strong>
        {{ now()->timezone('America/La_Paz')->format('d/m/Y H:i') }}
    </p>

    <h3>Parámetros utilizados</h3>

    @php
        $etiquetasInforme = [
            'demandaAnual' => 'Necesidad anual de adquisición (unidades/año)',
            'costoPedido' => 'Costo por pedido',
            'costoMantenimientoAnual' => 'Costo anual de mantener una unidad',
            'costoUnitario' => 'Precio unitario',
            'plazoEntregaDias' => 'Plazo de entrega (días)',
            'diasAnuales' => 'Días considerados por año',
        ];
    @endphp

    <table>
        <tbody>
            @foreach ($etiquetasInforme as $campo => $etiqueta)
                <tr>
                    <td>{{ $etiqueta }}</td>
                    <td>
                        {{ number_format($datos[$campo], 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>
        Los costos utilizan la moneda indicada en el formulario
        y en los resultados del análisis.
    </p>
</section>
        <section class="panel">
            <h2>2. Recomendación para los datos ingresados</h2>

            <p>Cantidad recomendada por pedido:</p>

            <div class="numero">
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
                    {{ (int) ceil($resultado['puntoReorden']) }} unidades.
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

            <div class="tabla">
                <table>
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
        // Usa 'BOB' si tu formulario está expresado en bolivianos.
        // La captura que mostraste estaba en USD.
        $monedaInforme = 'USD';

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

        <table>
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

        <table>
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
</main>
</body>
</html>