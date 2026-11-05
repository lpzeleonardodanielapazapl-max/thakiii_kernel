<?php

$campo = fn (string $label, string $rules, string $type = 'text', array $extra = []) => [
    'label' => $label, 'rules' => explode('|', $rules), 'type' => $type, ...$extra,
];
$estado = $campo('Estado', 'required|in:ACTIVO,INACTIVO', 'select', [
    'options' => ['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo'], 'default' => 'ACTIVO',
]);
$descripcion = $campo('Descripción y responsabilidades', 'nullable|string|max:2000', 'textarea');
$persona = $campo('Persona', 'required|string|max:20', 'select', ['catalogo' => 'personas', 'immutable' => true]);

return [
    'personas' => [
        'title' => 'Personas', 'singular' => 'persona', 'key' => 'codPersona',
        'description' => 'Los datos personales se registran una sola vez y se vinculan a empleados o clientes.',
        'labelKey' => 'nombres', 'related' => ['empleados.index' => 'Empleados', 'clientes.index' => 'Clientes'],
        'columns' => [
            ['key' => 'codPersona', 'label' => 'Código'], ['key' => 'nombres', 'label' => 'Nombres'],
            ['key' => 'apellidos', 'label' => 'Apellidos'], ['key' => 'email', 'label' => 'Correo'], ['key' => 'telefono', 'label' => 'Teléfono'],
        ],
        'fields' => [
            'nombres' => $campo('Nombres', 'required|string|max:100'),
            'apellidos' => $campo('Apellidos', 'required|string|max:100'),
            'tipoDocumento' => $campo('Tipo de documento', 'nullable|string|max:20'),
            'numeroDocumento' => $campo('Número de documento', 'nullable|string|max:30'),
            'email' => $campo('Correo electrónico', 'nullable|email|max:150', 'email'),
            'telefono' => $campo('Teléfono', 'nullable|string|max:30', 'tel'),
            'fechaNacimiento' => $campo('Fecha de nacimiento', 'nullable|date_format:Y-m-d|before_or_equal:today', 'date'),
            'genero' => $campo('Género (opcional)', 'nullable|string|max:20'),
            'direccion' => $campo('Dirección', 'nullable|string|max:200', 'textarea'),
        ],
    ],
    'empleados' => [
        'title' => 'Empleados', 'singular' => 'empleado', 'key' => 'codEmpleado', 'labelKey' => 'codEmpleado',
        'description' => 'Administra el cargo, el área de trabajo y las funciones de cada integrante del equipo.',
        'related' => ['personas.index' => 'Personas', 'cargos.index' => 'Cargos y responsabilidades', 'departamentos.index' => 'Departamentos', 'usuarios.index' => 'Usuarios'],
        'columns' => [
            ['key' => 'codEmpleado', 'label' => 'Código'],
            ['key' => '_persona', 'label' => 'Persona', 'linkModule' => 'personas', 'source' => 'codPersona'],
            ['key' => '_cargo', 'label' => 'Cargo'], ['key' => '_departamento', 'label' => 'Departamento'],
            ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'], ['key' => 'funciones', 'label' => 'Funciones', 'type' => 'functions'],
        ],
        'fields' => [
            'codPersona' => $persona,
            'codCargo' => $campo('Cargo o trabajo', 'required|string|max:20', 'select', ['catalogo' => 'cargos']),
            'codDep' => $campo('Departamento o área', 'required|string|max:20', 'select', ['catalogo' => 'departamentos']),
            'fechaIngreso' => $campo('Fecha de ingreso', 'required|date_format:Y-m-d', 'date'),
            'fechaRetiro' => $campo('Fecha de retiro', 'nullable|date_format:Y-m-d|after_or_equal:fechaIngreso', 'date'),
            'salario' => $campo('Salario en BOB', 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2', 'number', ['step' => '0.01', 'min' => '0']),
            'estado' => $estado,
            'esGuia' => $campo('Trabaja como guía', 'required|boolean', 'checkbox'),
            'esAsesor' => $campo('Atiende clientes como asesor', 'required|boolean', 'checkbox'),
            'esConductor' => $campo('Trabaja como conductor', 'required|boolean', 'checkbox'),
        ],
    ],
    'clientes' => [
        'title' => 'Clientes', 'singular' => 'cliente', 'key' => 'codCliente', 'labelKey' => 'codCliente',
        'description' => 'Vincula a cada cliente con sus datos personales y mantén actualizado su estado.',
        'related' => ['personas.index' => 'Personas', 'usuarios.index' => 'Usuarios'],
        'columns' => [
            ['key' => 'codCliente', 'label' => 'Código'],
            ['key' => '_persona', 'label' => 'Persona', 'linkModule' => 'personas', 'source' => 'codPersona'],
            ['key' => 'codigoCliente', 'label' => 'Referencia'], ['key' => 'estado', 'label' => 'Estado', 'type' => 'status'],
        ],
        'fields' => ['codPersona' => $persona, 'codigoCliente' => $campo('Referencia del cliente', 'nullable|string|max:30'), 'estado' => $estado],
    ],
    'usuarios' => [
        'title' => 'Usuarios', 'singular' => 'usuario', 'key' => 'idUsuario', 'labelKey' => 'email',
        'description' => 'Crea cuentas individuales, asigna uno de los tres roles y activa o desactiva su acceso.',
        'related' => ['roles.index' => 'Roles', 'empleados.index' => 'Empleados', 'clientes.index' => 'Clientes'],
        'columns' => [
            ['key' => 'idUsuario', 'label' => 'ID'], ['key' => 'username', 'label' => 'Usuario'], ['key' => 'email', 'label' => 'Correo'],
            ['key' => '_rol', 'label' => 'Rol', 'linkModule' => 'roles', 'source' => 'codRol'], ['key' => 'activo', 'label' => 'Acceso', 'type' => 'boolean'],
        ],
        'fields' => [
            'username' => $campo('Nombre de usuario', 'nullable|string|max:60'),
            'email' => $campo('Correo electrónico', 'required|email|max:150', 'email'),
            'codRol' => $campo('Rol del sistema', 'required|string|max:20', 'select', ['catalogo' => 'roles']),
            'activo' => $campo('Cuenta activa', 'required|boolean', 'checkbox', ['default' => true]),
            'password' => $campo('Contraseña', 'required|string|min:8|max:255|confirmed', 'password', ['editRules' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed']]),
        ],
    ],
    'cargos' => [
        'title' => 'Cargos', 'singular' => 'cargo', 'key' => 'codCargo', 'labelKey' => 'desCargo',
        'description' => 'Define los trabajos del equipo y describe las responsabilidades de cada cargo.',
        'related' => ['empleados.index' => 'Empleados', 'departamentos.index' => 'Departamentos'],
        'columns' => [['key' => 'codCargo', 'label' => 'Código'], ['key' => 'desCargo', 'label' => 'Cargo'], ['key' => 'descripcion', 'label' => 'Responsabilidades']],
        'fields' => ['desCargo' => $campo('Nombre del cargo', 'required|string|max:100'), 'descripcion' => $descripcion],
    ],
    'departamentos' => [
        'title' => 'Departamentos', 'singular' => 'departamento', 'key' => 'codDep', 'labelKey' => 'desDep',
        'description' => 'Organiza las áreas de trabajo y consulta el detalle de sus responsabilidades.',
        'related' => ['empleados.index' => 'Empleados', 'cargos.index' => 'Cargos'],
        'columns' => [['key' => 'codDep', 'label' => 'Código'], ['key' => 'desDep', 'label' => 'Departamento'], ['key' => 'descripcion', 'label' => 'Descripción']],
        'fields' => ['desDep' => $campo('Nombre del departamento', 'required|string|max:100'), 'descripcion' => $descripcion],
    ],
    'roles' => [
        'title' => 'Roles', 'singular' => 'rol', 'key' => 'codRol', 'labelKey' => 'desRol', 'canCreate' => false, 'canDelete' => false,
        'description' => 'El sistema utiliza ADMIN, ADMINISTRATIVO y CLIENTE. Puedes consultar cada rol y actualizar su descripción.',
        'related' => ['usuarios.index' => 'Usuarios'],
        'columns' => [['key' => 'codRol', 'label' => 'Código'], ['key' => 'desRol', 'label' => 'Nombre'], ['key' => 'descripcion', 'label' => 'Descripción']],
        'fields' => ['desRol' => $campo('Nombre del rol', 'required|in:ADMIN,ADMINISTRATIVO,CLIENTE', 'text', ['immutable' => true]), 'descripcion' => $descripcion],
    ],
];
