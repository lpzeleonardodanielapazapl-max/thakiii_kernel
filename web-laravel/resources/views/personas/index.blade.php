<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Personas - Thaki Voyage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">Thaki Voyage</h1>
        <p class="text-gray-600 mb-6">Personas registradas</p>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="text-left p-3">Código</th>
                        <th class="text-left p-3">Nombres</th>
                        <th class="text-left p-3">Apellidos</th>
                        <th class="text-left p-3">Email</th>
                        <th class="text-left p-3">Teléfono</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($personas as $p)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-mono text-sm">{{ $p['codPersona'] }}</td>
                            <td class="p-3">{{ $p['nombres'] }}</td>
                            <td class="p-3 font-semibold">{{ $p['apellidos'] }}</td>
                            <td class="p-3 text-gray-600">{{ $p['email'] ?? '-' }}</td>
                            <td class="p-3 text-gray-600">{{ $p['telefono'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-gray-500">Sin personas registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-sm text-gray-500">Total: {{ count($personas) }} personas</p>
    </div>
</body>
</html>