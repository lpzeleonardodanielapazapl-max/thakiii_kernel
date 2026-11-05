@extends('layouts.admin')

@section('titulo', 'Centro de operaciones')

@section('contenido')
    <div class="encabezado-pagina">
        <div>
            <p class="sobre-titulo">ADMINISTRACIÓN · THAKI VOYAGE</p>
            <h1>Centro de operaciones</h1>
            <p class="descripcion">
                Cada gran viaje empieza con una buena preparación.
            </p>
        </div>

        <a class="boton" href="{{ route('inventario.crear') }}">
            <span aria-hidden="true">＋</span> Registrar producto
        </a>
    </div>

    <section class="hero-thaki" aria-labelledby="titulo-hero">
        <div class="hero-texto">
            <span class="hero-etiqueta">EQUIPAMIENTO & EXPEDICIONES</span>
            <h2 id="titulo-hero">Todo listo para<br>el próximo camino.</h2>
            <p>
                Revisa tu inventario, identifica equipos que requieren
                atención y planifica tus próximas compras.
            </p>

            <a class="boton boton-crema" href="{{ url('/inventario') }}">
                Explorar inventario <span aria-hidden="true">↗</span>
            </a>
        </div>

        <svg class="paisaje" viewBox="0 0 700 360"
             preserveAspectRatio="xMidYMax slice" aria-hidden="true">
            <circle cx="530" cy="88" r="45" fill="#efbba1"/>
            <path d="M0 310 180 100 275 210 410 35 700 345V360H0Z"
                  fill="#244763"/>
            <path d="m310 160 100-125 109 137-71-27-37 25-39-45Z"
                  fill="#fff1e8" opacity=".8"/>
            <path d="M0 360 150 230 260 300 420 180 700 350V360Z"
                  fill="#12334d"/>
            <path d="M0 360 210 300 350 345 560 225 700 290V360Z"
                  fill="#082342"/>
            <path d="M480 360c-90-35-22-60 45-81 30-10 43-25 35-41"
                  fill="none" stroke="#efbba1" stroke-width="2"
                  stroke-dasharray="6 8"/>
            <circle cx="560" cy="238" r="5" fill="#d93643"/>
        </svg>

        <span class="hero-coordenada">BOLIVIA · CAMINOS POR DESCUBRIR</span>
    </section>

    @if ($error)
        <div class="aviso error" role="alert">
            <strong>No se pudieron cargar los indicadores.</strong>
            <p>{{ $error }}</p>
        </div>
    @endif

    <div class="titulo-seccion titulo-en-linea">
        <h2>Tu inventario, de un vistazo</h2>
        <span>Resumen de productos activos</span>
    </div>

    <section class="indicadores" aria-label="Resumen del inventario">
        <article class="indicador">
            <div class="indicador-titulo">
                <span>Productos activos</span>
                <span class="indicador-icono" aria-hidden="true">▦</span>
            </div>
            <strong>{{ $resumen ? number_format($resumen['productos'], 0, ',', '.') : '—' }}</strong>
            <small>Tipos de producto registrados.</small>
        </article>

        <article class="indicador">
            <div class="indicador-titulo">
                <span>Unidades registradas</span>
                <span class="indicador-icono" aria-hidden="true">◇</span>
            </div>
            <strong>{{ $resumen ? number_format($resumen['unidades'], 0, ',', '.') : '—' }}</strong>
            <small>Equipos vinculados a productos activos.</small>
        </article>

        <article class="indicador indicador-durazno">
            <div class="indicador-titulo">
                <span>Pendientes de arreglo</span>
                <span class="indicador-icono" aria-hidden="true">!</span>
            </div>
            <strong>{{ $resumen ? number_format($resumen['arreglo'], 0, ',', '.') : '—' }}</strong>
            <small>Unidades sin baja que requieren reparación.</small>
        </article>

        <article class="indicador">
            <div class="indicador-titulo">
                <span>Con baja registrada</span>
                <span class="indicador-icono" aria-hidden="true">−</span>
            </div>
            <strong>{{ $resumen ? number_format($resumen['bajas'], 0, ',', '.') : '—' }}</strong>
            <small>Unidades con fecha de baja.</small>
        </article>
    </section>

    <p class="nota">
        Las unidades registradas no equivalen a equipos disponibles.
        El resumen incluye los registros de demostración.
    </p>

    <div class="panel-inferior">
        <section class="herramientas" aria-labelledby="titulo-herramientas">
            <div class="titulo-seccion">
                <p class="sobre-titulo">TU ESPACIO DE TRABAJO</p>
                <h2 id="titulo-herramientas">Gestiona el próximo paso</h2>
            </div>

            <a class="acceso-fila" href="{{ url('/inventario') }}">
                <span class="numero">01</span>
                <div>
                    <h3>Inventario de equipos</h3>
                    <p>Consulta los productos y sus unidades registradas.</p>
                </div>
                <span class="flecha" aria-hidden="true">↗</span>
            </a>

            <a class="acceso-fila" href="{{ url('/inventario/abc') }}">
                <span class="numero">02</span>
                <div>
                    <h3>Clasificación ABC</h3>
                    <p>Identifica dónde se concentra el valor del inventario.</p>
                </div>
                <span class="flecha" aria-hidden="true">↗</span>
            </a>

            <a class="acceso-fila" href="{{ url('/inventario') }}">
                <span class="numero">03</span>
                <div>
                    <h3>Planificación de compras</h3>
                    <p>Elige un producto y abre «Analizar reposición».</p>
                </div>
                <span class="flecha" aria-hidden="true">↗</span>
            </a>
        </section>

        <section class="atencion" aria-labelledby="titulo-atencion">
            <div>
                <span class="atencion-etiqueta">CUIDADO DEL EQUIPO</span>
                <h2 id="titulo-atencion">Cada detalle cuenta.</h2>

                @if (!$resumen)
                    <p>
                        No fue posible consultar los equipos
                        pendientes de arreglo.
                    </p>
                @elseif ($resumen['arreglo'] > 0)
                    <div class="atencion-cantidad">
                        {{ number_format($resumen['arreglo'], 0, ',', '.') }}
                    </div>
                    <p>
                        {{ $resumen['arreglo'] == 1
                            ? 'unidad sin baja está marcada para reparación.'
                            : 'unidades sin baja están marcadas para reparación.' }}
                        Revisa estos registros antes de planificar su uso.
                    </p>
                @else
                    <p class="atencion-mensaje">Sin arreglos pendientes registrados.</p>
                    <p>
                        Consulta el inventario para revisar
                        la información de tus equipos.
                    </p>
                @endif
            </div>

            <a href="{{ url('/inventario') }}" class="enlace-atencion">
                Consultar inventario <span aria-hidden="true">→</span>
            </a>
        </section>
    </div>
@endsection