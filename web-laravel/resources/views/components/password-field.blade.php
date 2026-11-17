@props(['name' => 'password', 'label' => 'Contraseña', 'autocomplete' => 'new-password', 'minimum' => 8])
@php($inputId = 'acceso-'.$name)
<div class="campo-contrasena">
    <label for="{{ $inputId }}">{{ $label }}</label>
    <div class="control-contrasena">
        <input id="{{ $inputId }}" name="{{ $name }}" type="password" @if($minimum) minlength="{{ $minimum }}" @endif maxlength="255" autocomplete="{{ $autocomplete }}" required>
        <button type="button" data-password-toggle aria-controls="{{ $inputId }}" aria-pressed="false" aria-label="Mostrar {{ mb_strtolower($label) }}" hidden>Mostrar</button>
    </div>
</div>
@once
    @push('styles')<link rel="stylesheet" href="{{ asset('css/password.css') }}?v={{ filemtime(public_path('css/password.css')) }}">@endpush
    @push('scripts')<script src="{{ asset('js/password.js') }}?v={{ filemtime(public_path('js/password.js')) }}" defer></script>@endpush
@endonce
