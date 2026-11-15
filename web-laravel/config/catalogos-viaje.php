<?php

$campoViaje = fn (string $label, string $rules, string $type = 'text', array $extra = []): array => ['label' => $label, 'rules' => explode('|', $rules), 'type' => $type, ...$extra];
$activoViaje = $campoViaje('Estado', 'required|in:ACTIVO,INACTIVO', 'select', ['options' => ['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo'], 'default' => 'ACTIVO']);
$numeroViaje = fn (string $label, int $max = 1000): array => $campoViaje($label, 'required|integer|min:1|max:'.$max, 'number', ['min' => 1]);
$dineroViaje = fn (string $label): array => $campoViaje($label, 'required|numeric|min:0|max:9999999999.99|decimal:0,2', 'number', ['min' => 0, 'step' => '0.01']);
$referenciaViaje = fn (string $label, string $catalogo): array => $campoViaje($label, 'required|string|max:20', 'select', ['catalogo' => $catalogo, 'immutable' => true]);
$moduloViaje = fn (string $title, string $singular, string $description, array $columns, array $fields, array $related = []): array => [
    'title' => $title, 'singular' => $singular, 'description' => $description, 'key' => 'codigo', 'labelKey' => 'nombre', 'canDelete' => false, 'eyebrow' => 'ALOJAMIENTOS Y MOVILIDAD',
    'columns' => $columns, 'fields' => $fields, 'related' => $related,
];

return [
    'hoteles' => $moduloViaje('Hoteles y alojamientos', 'alojamiento', 'Registra hoteles, servicios y datos de contacto. Desactiva las opciones que ya no se deben ofrecer.', [
        ['key' => 'nombre', 'label' => 'Alojamiento'], ['key' => 'ciudad', 'label' => 'Ciudad'], ['key' => 'categoria', 'label' => 'Estrellas'], ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'],
    ], [
        'nombre' => $campoViaje('Nombre del alojamiento', 'required|string|max:150'), 'ciudad' => $campoViaje('Ciudad', 'required|string|max:100'),
        'pais' => $campoViaje('País', 'required|string|max:80', 'text', ['default' => 'Bolivia']), 'categoria' => $campoViaje('Categoría en estrellas (0 si no aplica)', 'required|integer|min:0|max:5', 'number', ['min' => 0, 'default' => 0]),
        'descripcion' => $campoViaje('Descripción', 'nullable|string|max:4000', 'textarea'), 'direccion' => $campoViaje('Dirección', 'required|string|max:200'),
        'telefono' => $campoViaje('Teléfono de contacto', 'nullable|string|max:50', 'tel'), 'email' => $campoViaje('Correo de contacto', 'nullable|email|max:150', 'email'),
        'sitioWeb' => $campoViaje('Sitio web', 'nullable|url:http,https|max:200', 'url'), 'imagenUrl' => $campoViaje('URL de la fotografía del alojamiento', 'nullable|url:http,https|max:300', 'url'),
        'servicios' => $campoViaje('Servicios y comodidades', 'nullable|string|max:4000', 'textarea'), 'checkIn' => $campoViaje('Hora de entrada', 'nullable|date_format:H:i', 'time'),
        'checkOut' => $campoViaje('Hora de salida', 'nullable|date_format:H:i', 'time'), 'estado' => $activoViaje,
    ], ['habitaciones.index' => 'Habitaciones', 'tarifas-hotel.index' => 'Tarifas', 'publico.alojamientos' => 'Vista del cliente']),
    'habitaciones' => $moduloViaje('Tipos de habitación', 'tipo de habitación', 'Define la capacidad y las camas de cada alojamiento. Añade una tarifa para mostrar esta habitación al cliente.', [
        ['key' => 'nombre', 'label' => 'Habitación'], ['key' => '_hotel', 'label' => 'Hotel', 'linkModule' => 'hoteles', 'source' => 'codHotel'], ['key' => 'capacidad', 'label' => 'Personas'], ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'],
    ], [
        'codHotel' => $referenciaViaje('Alojamiento', 'hoteles'), 'nombre' => $campoViaje('Nombre del tipo de habitación', 'required|string|max:100'),
        'descripcion' => $campoViaje('Descripción y detalles', 'nullable|string|max:4000', 'textarea'), 'capacidad' => $numeroViaje('Capacidad de personas'),
        'numCamas' => $numeroViaje('Cantidad de camas'), 'tipoCamas' => $campoViaje('Tipo de camas', 'nullable|string|max:100'), 'estado' => $activoViaje,
    ], ['hoteles.index' => 'Alojamientos', 'tarifas-hotel.index' => 'Tarifas']),
    'tarifas-hotel' => $moduloViaje('Tarifas de alojamiento', 'tarifa de alojamiento', 'Configura precio por noche y vigencia. Las tarifas de una habitación no deben solaparse; ajusta sus fechas para cambiar de temporada.', [
        ['key' => '_habitacion', 'label' => 'Habitación', 'linkModule' => 'habitaciones', 'source' => 'codTipoHab'], ['key' => 'fechaInicio', 'label' => 'Desde', 'type' => 'date'], ['key' => 'fechaFin', 'label' => 'Hasta', 'type' => 'date'], ['key' => 'precioNoche', 'label' => 'Precio por noche'], ['key' => 'moneda', 'label' => 'Moneda'],
    ], [
        'codTipoHab' => $referenciaViaje('Habitación', 'habitaciones'), 'fechaInicio' => $campoViaje('Válida desde', 'required|date_format:Y-m-d', 'date'),
        'fechaFin' => $campoViaje('Válida hasta (vacío: sin fecha final)', 'nullable|date_format:Y-m-d|after_or_equal:fechaInicio', 'date'), 'precioNoche' => $dineroViaje('Precio por noche'),
        'moneda' => $campoViaje('Moneda', 'required|in:BOB,USD,EUR', 'select', ['options' => ['BOB' => 'BOB', 'USD' => 'USD', 'EUR' => 'EUR'], 'default' => 'BOB']),
        'incluyeDesayuno' => $campoViaje('Incluye desayuno', 'required|boolean', 'checkbox'), 'observaciones' => $campoViaje('Condiciones y observaciones', 'nullable|string|max:4000', 'textarea'),
    ], ['hoteles.index' => 'Alojamientos', 'habitaciones.index' => 'Habitaciones']),
    'vehiculos' => $moduloViaje('Vehículos', 'vehículo', 'Mantén la flota, las capacidades y su estado. La disponibilidad indicada no confirma una asignación para fechas concretas.', [
        ['key' => 'placa', 'label' => 'Placa'], ['key' => 'tipo', 'label' => 'Tipo'], ['key' => 'marca', 'label' => 'Marca'], ['key' => 'modelo', 'label' => 'Modelo'], ['key' => 'capacidad', 'label' => 'Pasajeros'], ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'],
    ], [
        'placa' => $campoViaje('Placa', 'required|string|max:20'), 'marca' => $campoViaje('Marca', 'required|string|max:80'), 'modelo' => $campoViaje('Modelo', 'required|string|max:80'),
        'anio' => $campoViaje('Año', 'nullable|integer|min:1900|max:'.(date('Y') + 1), 'number', ['min' => 1900]), 'color' => $campoViaje('Color', 'nullable|string|max:50'),
        'capacidad' => $numeroViaje('Capacidad de pasajeros'), 'tipo' => $campoViaje('Tipo de vehículo', 'required|string|max:50'),
        'estado' => $campoViaje('Estado', 'required|in:DISPONIBLE,MANTENIMIENTO,INACTIVO,EN_USO', 'select', ['options' => ['DISPONIBLE' => 'Disponible', 'MANTENIMIENTO' => 'En mantenimiento', 'INACTIVO' => 'Inactivo', 'EN_USO' => 'En uso'], 'default' => 'DISPONIBLE']),
        'observaciones' => $campoViaje('Detalles y observaciones', 'nullable|string|max:4000', 'textarea'),
    ], ['servicios-transporte.index' => 'Servicios de traslado', 'conductores.index' => 'Conductores']),
    'conductores' => [...$moduloViaje('Conductores', 'conductor', 'Vincula un empleado con función de conductor y registra su licencia. Para activarlo, la licencia y su ficha laboral deben estar vigentes.', [
        ['key' => '_empleado', 'label' => 'Empleado', 'linkModule' => 'empleados', 'source' => 'codEmpleado'], ['key' => 'licencia', 'label' => 'Licencia'], ['key' => 'vencimientoLic', 'label' => 'Vence', 'type' => 'date'], ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'],
    ], [
        'codEmpleado' => $referenciaViaje('Empleado con función de conductor', 'empleados'), 'licencia' => $campoViaje('Número de licencia', 'required|string|max:50'),
        'categoriaLic' => $campoViaje('Categoría de licencia', 'required|string|max:10'), 'vencimientoLic' => $campoViaje('Vencimiento de licencia', 'required|date_format:Y-m-d', 'date'),
        'experienciaAnios' => $campoViaje('Años de experiencia', 'nullable|integer|min:0|max:80', 'number', ['min' => 0]), 'estado' => $activoViaje,
    ], ['empleados.index' => 'Personal', 'vehiculos.index' => 'Vehículos']), 'labelKey' => 'licencia'],
    'servicios-transporte' => $moduloViaje('Servicios de traslado', 'servicio de traslado', 'Define el servicio, la ruta, la capacidad y el precio base que el asesor puede ofrecer al cliente.', [
        ['key' => 'nombre', 'label' => 'Servicio'], ['key' => 'tipo', 'label' => 'Tipo'], ['key' => 'origen', 'label' => 'Origen'], ['key' => 'destino', 'label' => 'Destino'], ['key' => 'precioBase', 'label' => 'Precio base'], ['key' => 'moneda', 'label' => 'Moneda'], ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'],
    ], [
        'nombre' => $campoViaje('Nombre del servicio', 'required|string|max:150'), 'descripcion' => $campoViaje('Descripción y servicios incluidos', 'nullable|string|max:4000', 'textarea'),
        'tipo' => $campoViaje('Tipo de traslado (privado, compartido, fluvial…)', 'required|string|max:50'), 'origen' => $campoViaje('Origen', 'required|string|max:150'), 'destino' => $campoViaje('Destino', 'required|string|max:150'),
        'distanciaKm' => $campoViaje('Distancia en km', 'nullable|numeric|min:0|max:99999999.99|decimal:0,2', 'number', ['min' => 0, 'step' => '0.01']), 'duracionMin' => $campoViaje('Duración en minutos', 'nullable|integer|min:0|max:100000', 'number', ['min' => 0]),
        'precioBase' => $dineroViaje('Precio base del servicio'), 'moneda' => $campoViaje('Moneda', 'required|in:BOB,USD,EUR', 'select', ['options' => ['BOB' => 'BOB', 'USD' => 'USD', 'EUR' => 'EUR'], 'default' => 'BOB']),
        'capacidadMax' => $numeroViaje('Máximo de pasajeros'), 'estado' => $activoViaje,
    ], ['vehiculos.index' => 'Vehículos', 'conductores.index' => 'Conductores', 'publico.transporte' => 'Vista del cliente']),
];
