<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empleados - Thaki Voyage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">Thaki Voyage</h1>
        <p class="text-gray-600 mb-6">Empleados</p>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="text-left p-3">Código</th>
                        <th class="text-left p-3">Persona</th>
                        <th class="text-left p-3">Cargo</th>
                        <th class="text-left p-3">Salario</th>
                        <th class="text-left p-3">Estado</th>
                        <th class="text-left p-3">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($empleados as $e)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-mono text-sm">{{ $e['codEmpleado'] }}</td>
                            <td class="p-3">{{ $e['codPersona'] }}</td>
                            <td class="p-3">{{ $e['codCargo'] ?? '-' }}</td>
                            <td class="p-3">{{ $e['salario'] ?? '-' }}</td>
                            <td class="p-3">{{ $e['estado'] ?? '-' }}</td>
                            <td class="p-3 text-sm">
                                @if($e['esGuia'] ?? false) <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">Guía</span> @endif
                                @if($e['esAsesor'] ?? false) <span class="bg-green-100 text-green-800 px-2 py-1 rounded">Asesor</span> @endif
                                @if($e['esConductor'] ?? false) <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Conductor</span> @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Sin empleados registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-sm text-gray-500">Total: {{ count($empleados) }} empleados</p>
    </div>
</body>
</html>