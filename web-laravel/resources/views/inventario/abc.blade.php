<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Prioridades del inventario · Thaki Voyage</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f3f5f7;
            color: #24343b;
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }

        main {
            max-width: 1250px;
            margin: 30px auto;
            padding: 20px;
        }

        h1, h2 { color: #164c45; }
        a { color: #164c45; }

        .panel {
            background: white;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #dce5e3;
            margin: 20px 0;
        }

        .resumen {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .numero {
            display: block;
            font-size: 30px;
            font-weight: bold;
        }

        .tabla { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dce5e3;
        }

        th { background: #edf4f1; }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: bold;
        }

        .A { background: #fbe1df; color: #8b2020; }
        .B { background: #fff0c7; color: #705000; }
        .C { background: #dcefe5; color: #185437; }

        small { color: #53676e; }
        .derecha { text-align: right; }

        @media (max-width: 650px) {
            .resumen { grid-template-columns: 1fr; }
            main { padding: 12px; }
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
        $formato = fn ($valor) => number_format($valor, 2, ',', '.');
        $productos = collect($abc['productos']);
    @endphp

    <a href="{{ route('inventario.index') }}">← Volver al inventario</a>

    <h1>¿Qué productos necesitan mayor control?</h1>

    <p>
        La clasificación ABC ordena los productos según el valor
        de sus unidades registradas sin baja.
    </p>

    <section class="panel">
        <h2>Valor de referencia analizado</h2>

        <span class="numero">
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

    <div class="resumen">
        <section class="panel">
            <span class="badge A">A · Mayor prioridad</span>
            <span class="numero">
                {{ $productos->where('clasificacion', 'A')->count() }}
            </span>
            <p>
                Concentran aproximadamente el primer 80 % del valor.
                Conviene un control más frecuente y detallado.
            </p>
        </section>

        <section class="panel">
            <span class="badge B">B · Prioridad intermedia</span>
            <span class="numero">
                {{ $productos->where('clasificacion', 'B')->count() }}
            </span>
            <p>
                Amplían la cobertura hasta aproximadamente el 95 %.
                Requieren seguimiento periódico.
            </p>
        </section>

        <section class="panel">
            <span class="badge C">C · Menor concentración de valor</span>
            <span class="numero">
                {{ $productos->where('clasificacion', 'C')->count() }}
            </span>
            <p>
                Representan el valor restante. Mantienen su control
                habitual, aunque su participación económica sea menor.
            </p>
        </section>
    </div>

    <section class="panel">
        <h2>Productos ordenados por valor</h2>

        <div class="tabla">
            <table>
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
                        <tr>
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
                                <a href="{{ route('inventario.analizar', [
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
</main>
</body>
</html>