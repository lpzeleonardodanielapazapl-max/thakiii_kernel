@props(['value'])
@php
    [$texto, $tono] = match($value) {
        'RECIBIDA' => ['Solicitud recibida', 'azul'],
        'ENVIADA' => ['Pendiente de respuesta', 'ambar'],
        'REEMPLAZADA' => ['Versión anterior', 'gris'],
        'ACEPTADA' => ['Propuesta aceptada', 'verde'],
        'PENDIENTE_ANTICIPO' => ['Pendiente del anticipo', 'ambar'],
        'CONFIRMADA', 'CONFIRMADO' => ['Confirmado', 'verde'],
        'CANCELADA', 'CANCELADO' => ['Cancelado', 'gris'],
        'FINALIZADA', 'FINALIZADO' => ['Finalizado', 'gris'],
        default => [ucfirst(mb_strtolower(str_replace('_', ' ', $value ?? 'Sin estado'))), 'gris'],
    };
@endphp
<span class="estado-viaje estado-viaje-{{ $tono }}">{{ $texto }}</span>
