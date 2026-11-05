@extends('layouts.admin')

@section('titulo', 'Registrar producto')

@section('contenido')
    <div class="cabecera">
        <div>
            <p class="sobre-titulo">INVENTARIO</p>

            <h1>Registrar producto.</h1>

            <p class="descripcion">
                Define el producto. Después podrás registrar
                cada unidad física con su código individual.
            </p>
        </div>

        <a class="boton" href="{{ url('/inventario') }}">
            Ver inventario
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

        <form method="POST" action="{{ route('inventario.guardar') }}">
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
                        value="{{ old('nombre') }}"
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
                                @selected(old('codClase') === $opcion['codigo'])
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
                                @selected(old('codMarca') === $opcion['codigo'])
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
                        value="{{ old('modelo') }}"
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
                        value="{{ old('color') }}"
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
                                    old('codUm', 'UM-001') === $opcion['codigo']
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

            <div class="formulario-acciones">
                <button class="boton" type="submit">
                    Guardar producto
                </button>

                <a href="{{ url('/inventario') }}">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
@endsection