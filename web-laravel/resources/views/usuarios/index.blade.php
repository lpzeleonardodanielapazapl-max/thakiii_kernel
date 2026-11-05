<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Thaki Voyage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">Thaki Voyage</h1>
        <p class="text-gray-600 mb-6">Usuarios del sistema</p>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="text-left p-3">ID</th>
                        <th class="text-left p-3">Email</th>
                        <th class="text-left p-3">Rol</th>
                        <th class="text-left p-3">Empleado</th>
                        <th class="text-left p-3">Cliente</th>
                        <th class="text-left p-3">Activo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $u)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-mono text-sm">{{ $u['idUsuario'] }}</td>
                            <td class="p-3">{{ $u['email'] }}</td>
                            <td class="p-3">{{ $u['codRol'] ?? '-' }}</td>
                            <td class="p-3 text-gray-600">{{ $u['codEmpleado'] ?? '-' }}</td>
                            <td class="p-3 text-gray-600">{{ $u['codCliente'] ?? '-' }}</td>
                            <td class="p-3">
                                @if($u['activo'] ?? false)
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Sí</span>
                                @else
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">No</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Sin usuarios registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-sm text-gray-500">Total: {{ count($usuarios) }} usuarios</p>
    </div>
</body>
</html>