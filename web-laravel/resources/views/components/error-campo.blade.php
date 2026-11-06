@props(['name'])
@error($name)<small id="error-{{ str_replace('.', '-', $name) }}" class="error-campo" role="alert">{{ $message }}</small>@enderror
