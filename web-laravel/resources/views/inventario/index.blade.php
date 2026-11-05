<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Inventario · Thaki Voyage</title>

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f5f7;
            color: #17252a;
            font-family: Arial, sans-serif;
        }
        main {
            max-width: 1200px;
            margin: 35px auto;
            padding: 20px;
        }
        h1 { color: #164c45; }
        .tabla {
            background: white;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e0e6e8;
        }
        th { background: #edf4f1; }
        .boton {
            display: inline-block;
            padding: 10px 14px;
            background: #164c45;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
    <link
    rel="stylesheet"
    href="{{ asset('css/thaki-admin.css') }}?v=1"
>
</head>
<body>
<main>
    <h1>Inventario de Thaki Voyage</h1>
    <p>
    <a class="boton" href="{{ route('inventario.abc') }}">
        Ver prioridades ABC
    </a>
</p>
    <p>Productos y unidades registrados en el almacén.</p>

    <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Unidades registradas</th>
                    <th>Requieren arreglo</th>
                    <th>Con baja</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td>
                            <strong>{{ $producto['nombre'] }}</strong><br>
                            <small>
                                {{ $producto['codigo'] }}
                                · {{ $producto['marca'] }}
                            </small>
                        </td>

                        <td>{{ $producto['categoria'] }}</td>
                        <td>{{ $producto['unidadesRegistradas'] }}</td>
                        <td>{{ $producto['unidadesRequierenArreglo'] }}</td>
                        <td>{{ $producto['unidadesConBaja'] }}</td>

                        <td>
                            <a
                                class="boton"
                                href="{{ route('inventario.analizar', [
                                    'codigo' => $producto['codigo']
                                ]) }}"
                            >
                                Analizar reposición
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No hay productos activos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p>
        Las cantidades reflejan los registros existentes.
        La disponibilidad requiere comprobar salidas y recepciones.
    </p>
</main>
</body>
</html>