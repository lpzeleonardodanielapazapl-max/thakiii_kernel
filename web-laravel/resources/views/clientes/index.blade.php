<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes - Thaki Voyage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">Thaki Voyage</h1>
        <p class="text-gray-600 mb-6">Clientes</p>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="text-left p-3">Código</th>
                        <th class="text-left p-3">Persona</th>
                        <th class="text-left p-3">Código cliente</th>
                        <th class="text-left p-3">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $c)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-mono text-sm">{{ $c['codCliente'] }}</td>
                            <td class="p-3">{{ $c['codPersona'] }}</td>
                            <td class="p-3">{{ $c['codigoCliente'] ?? '-' }}</td>
                            <td class="p-3">{{ $c['estado'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-gray-500">Sin clientes registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-sm text-gray-500">Total: {{ count($clientes) }} clientes</p>
    </div>
</body>
</html>