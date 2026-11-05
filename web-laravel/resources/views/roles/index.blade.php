<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles - Thaki Voyage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">Thaki Voyage</h1>
        <p class="text-gray-600 mb-6">Roles del Sistema</p>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="text-left p-3">Código</th>
                        <th class="text-left p-3">Nombre</th>
                        <th class="text-left p-3">Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $rol)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-mono text-sm">{{ $rol['codRol'] }}</td>
                            <td class="p-3 font-semibold">{{ $rol['desRol'] }}</td>
                            <td class="p-3 text-gray-600">{{ $rol['descripcion'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-6 text-center text-gray-500">
                                No hay roles registrados o Quarkus no responde
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-sm text-gray-500">
            Total: {{ count($roles) }} roles
        </p>
    </div>
</body>
</html>