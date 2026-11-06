@props(['value' => null, 'type' => 'text', 'currency' => 'BOB'])
@if ($value === null || $value === '')
    —
@elseif ($type === 'date')
    {{ \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') }}
@elseif ($type === 'datetime')
    {{ \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') }}
@elseif ($type === 'money')
    {{ number_format((float) $value, 2, ',', '.') }}{{ $currency ? ' '.$currency : '' }}
@elseif ($type === 'boolean')
    {{ $value ? 'Sí' : 'No' }}
@else
    <span class="reporte-texto">{{ $value }}</span>
@endif
