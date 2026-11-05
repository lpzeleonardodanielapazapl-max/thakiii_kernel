@extends('layouts.admin')

@section('titulo', ($editing ?? false) ? 'Editar producto' : 'Registrar producto')

@section('contenido')
    <div class="cabecera">
        <div>
            <p class="sobre-titulo">INVENTARIO</p>

            <h1>{{ ($editing ?? false) ? 'Cada detalle, actualizado.' : 'El comienzo de un nuevo equipo.' }}</h1>

            <p class="descripcion">
                Define el producto. Después podrás registrar
                cada unidad física con su código individual.
            </p>
        </div>

        <a class="boton boton-secundario" href="{{ route('inventario.index') }}">
            <x-icon name="back"/> Ver inventario
        </a>
    </div>

    @if (session('exito'))
        <div class="aviso" role="status">
            {{ session('exito') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="aviso error" role="alert">
            <strong>No se pudo completar el formulario:</strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="formulario-panel">
        <h2>Datos del producto</h2>

        <p class="nota">
            Los campos con * son obligatorios.
            El código del producto se genera automáticamente.
        </p>

        <form method="POST" action="{{ ($editing ?? false) ? route('inventario.actualizar', ['codigo' => $producto['codigo']]) : route('inventario.guardar') }}">
            @if ($editing ?? false) @method('PUT') @endif
            @csrf

            <div class="formulario-grid">
                <div class="ancho-completo">
                    <label for="nombre">Nombre del producto *</label>

                    <input
                        id="nombre"
                        name="nombre"
                        type="text"
                        maxlength="200"
                        placeholder="Ejemplo: Carpa Doite para tres personas"
                        value="{{ old('nombre', $producto['nombre'] ?? '') }}"
                        required
                    >
                </div>

                <div>
                    <label for="codClase">Clase de producto *</label>

                    <select id="codClase" name="codClase" required>
                        <option value="">Selecciona una clase</option>

                        @foreach ($catalogos['clases'] as $opcion)
                            <option
                                value="{{ $opcion['codigo'] }}"
                                @selected(old('codClase', $producto['codClase'] ?? '') === $opcion['codigo'])
                            >
                                {{ $opcion['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="codMarca">Marca</label>

                    <select id="codMarca" name="codMarca">
                        <option value="">Sin marca registrada</option>

                        @foreach ($catalogos['marcas'] as $opcion)
                            <option
                                value="{{ $opcion['codigo'] }}"
                                @selected(old('codMarca', $producto['codMarca'] ?? '') === $opcion['codigo'])
                            >
                                {{ $opcion['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="modelo">Modelo</label>

                    <input
                        id="modelo"
                        name="modelo"
                        type="text"
                        maxlength="100"
                        placeholder="Modelo o referencia comercial"
                        value="{{ old('modelo', $producto['modelo'] ?? '') }}"
                    >
                </div>

                <div>
                    <label for="color">Color</label>

                    <input
                        id="color"
                        name="color"
                        type="text"
                        maxlength="80"
                        placeholder="Ejemplo: Verde"
                        value="{{ old('color', $producto['color'] ?? '') }}"
                    >
                </div>

                <div>
                    <label for="codUm">Unidad de medida *</label>

                    <select id="codUm" name="codUm" required>
                        <option value="">Selecciona una unidad</option>

                        @foreach ($catalogos['unidadesMedida'] as $opcion)
                            <option
                                value="{{ $opcion['codigo'] }}"
                                @selected(
                                    old('codUm', $producto['codUm'] ?? 'UM-001') === $opcion['codigo']
                                )
                            >
                                {{ $opcion['nombre'] }}
                            </option>
                        @endforeach
                    </select>

                    <small>
                        Para equipos identificados individualmente,
                        utiliza «Unidad».
                    </small>
                </div>
            </div>

            @if ($editing ?? false)
                <div class="formulario-grid">
                    <div><label for="stockMinimo">Stock mínimo de referencia *</label><input id="stockMinimo" name="stockMinimo" type="number" min="0" step="0.01" value="{{ old('stockMinimo', $producto['stockMinimo']) }}" required></div>
                    <div><label for="estado">Estado *</label><select id="estado" name="estado" required><option value="ACTIVO" @selected(old('estado', $producto['estado']) === 'ACTIVO')>Activo</option><option value="INACTIVO" @selected(old('estado', $producto['estado']) === 'INACTIVO')>Inactivo</option></select><small>Inactivar conserva las unidades y el historial del producto.</small></div>
                </div>
            @endif
            <div class="formulario-acciones">
                <button class="boton" type="submit">
                    <x-icon name="plus"/> {{ ($editing ?? false) ? 'Guardar cambios' : 'Guardar producto' }}
                </button>

                <a class="boton boton-secundario" href="{{ route('inventario.index') }}">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
@endsection
