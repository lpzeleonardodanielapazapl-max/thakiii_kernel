<?php

declare(strict_types=1);

date_default_timezone_set('America/La_Paz');
$results = [];
$password = json_decode(file_get_contents(__DIR__.'/.credenciales-qa.json'), true, 512, JSON_THROW_ON_ERROR)['password'];
$run = date('His');

function check(string $name, bool $ok, array $detail = []): void
{
    global $results;
    $results[] = ['test' => $name, 'passed' => $ok, ...$detail];
    echo ($ok ? 'PASS ' : 'FAIL ').$name.PHP_EOL;
}

function request(string $name, string $method, string $path, int|array $expected = 200, ?array $body = null, ?string $token = null, ?string $cookie = null): array
{
    $api = str_starts_with($path, '/api/');
    $ch = curl_init(($api ? 'http://127.0.0.1:18080' : 'http://127.0.0.1:18000').$path);
    $headers = [];
    $options = [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
            if (str_contains($line, ':')) { [$key,$value] = explode(':', $line, 2); $headers[strtolower(trim($key))] = trim($value); }
            return strlen($line);
        }];
    $sendHeaders = [$api ? 'Accept: application/json' : 'Accept: text/html'];
    if ($token !== null) { $sendHeaders[] = 'Authorization: Bearer '.$token; }
    if ($body !== null) {
        $sendHeaders[] = $api ? 'Content-Type: application/json' : 'Content-Type: application/x-www-form-urlencoded';
        $options[CURLOPT_POSTFIELDS] = $api ? json_encode((object) $body, JSON_THROW_ON_ERROR) : http_build_query($body);
    }
    if ($cookie !== null) {
        $options[CURLOPT_COOKIEFILE] = __DIR__.'/cookies-'.$cookie.'.txt';
        $options[CURLOPT_COOKIEJAR] = $options[CURLOPT_COOKIEFILE];
    }
    $options[CURLOPT_HTTPHEADER] = $sendHeaders;
    if ($method === 'HEAD') { $options[CURLOPT_NOBODY] = true; }
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $time = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    $expected = (array) $expected;
    $ok = in_array($status, $expected, true);
    check($name, $ok, ['method' => $method, 'path' => $path, 'expected' => $expected, 'status' => $status, 'duration_ms' => $time,
        ...($ok ? [] : ['error' => $json['mensaje'] ?? curl_error($ch) ?: 'Respuesta HTTP inesperada'])]);
    return ['status' => $status, 'json' => $json, 'html' => $raw ?: '', 'headers' => $headers];
}

function api(string $name, string $method, string $path, int|array $expected = 200, ?array $body = null, ?string $token = null): array
{
    return request($name, $method, $path, $expected, $body, $token)['json'] ?? [];
}

function csrf(string $html): string
{
    if (!preg_match('/name="_token"[^>]*value="([^"]+)"/', $html, $match)) { throw new RuntimeException('Falta el token CSRF en el formulario.'); }
    return html_entity_decode($match[1]);
}

function webLogin(string $cookie, string $email, string $password): void
{
    $page = request('Web formulario de acceso '.$cookie, 'GET', '/ingresar', 200, null, null, $cookie);
    $login = request('Web inicio de sesión '.$cookie, 'POST', '/ingresar', 302,
        ['_token' => csrf($page['html']), 'email' => $email, 'password' => $password], null, $cookie);
    check('Web destino después de ingresar '.$cookie, str_contains($login['headers']['location'] ?? '', $cookie === 'cliente' ? '/mi-viaje' : '/admin'));
}

function section(string $name, callable $run): void
{
    try { $run(); } catch (Throwable $e) { check($name.' completado', false, ['error' => $e->getMessage()]); }
}

$admin = $staff = $client = $other = null;
$product = $unit = $solicitud = $propuesta = $reserva = null;
$date = date('Y-m-d');
$start = date('Y-m-d', strtotime('+30 days'));
$end = date('Y-m-d', strtotime('+31 days'));

section('Salud y protección', function () {
    $ping = request('API ping público', 'GET', '/api/v1/health/ping');
    check('API ping identifica servicio', ($ping['json']['service'] ?? null) === 'thaki-api');
    request('API ping HEAD público', 'HEAD', '/api/v1/health/ping');
    $unauthorized = request('API diagnóstico MySQL protegido', 'GET', '/api/v1/health/db', 401);
    check('API 401 conserva desafío Bearer', str_contains($unauthorized['headers']['www-authenticate'] ?? '', 'Bearer'));
    check('API 401 conserva identificador de solicitud', isset($unauthorized['headers']['x-request-id']));
    request('API ruta desconocida devuelve 404', 'GET', '/api/publico/no-existe', 404);
    api('API no permite crear administrador en producción', 'POST', '/api/acceso/inicializar', 404, []);
    foreach (['/api/v1/usuarios','/api/cliente/resumen','/api/inventario/productos','/api/atencion/panel'] as $path) {
        request('API exige sesión '.$path, 'GET', $path, 401);
    }
    foreach (['/','/ingresar','/registro','/alojamientos','/traslados','/inspiracion/uyuni','/up'] as $path) {
        request('Web pública '.$path, 'GET', $path);
    }
    request('Web planificador valida campos obligatorios', 'GET', '/planificar-viaje', 302);
    $plan = request('Web planificador conserva preferencias y redirige', 'GET', '/planificar-viaje?destinos=Uyuni&personas=2&moneda=BOB', 302);
    check('Web planificador dirige al registro', str_contains($plan['headers']['location'] ?? '', '/registro'));
    foreach (['/admin','/inventario','/usuarios','/atencion','/mi-viaje'] as $path) {
        $r = request('Web exige sesión '.$path, 'GET', $path, 302);
        check('Web redirige al ingreso '.$path, str_contains($r['headers']['location'] ?? '', '/ingresar'));
    }
    request('Web ruta desconocida', 'GET', '/no-existe', 404);
    request('Web rechaza POST sin CSRF', 'POST', '/ingresar', 419, ['email'=>'x@example.test','password'=>'invalid']);
    foreach (glob(dirname(__DIR__).'/web-laravel/public/css/*.css') as $file) { request('Web CSS '.basename($file), 'GET', '/css/'.basename($file)); }
    foreach (glob(dirname(__DIR__).'/web-laravel/public/js/*.js') as $file) { request('Web JS '.basename($file), 'GET', '/js/'.basename($file)); }
});

section('Acceso y privacidad', function () use (&$admin,&$staff,&$client,&$other,$password,$run) {
    $admin = api('API login administrador','POST','/api/acceso/ingresar',200,['email'=>'admin-qa@example.test','password'=>$password])['token'] ?? null;
    $staff = api('API login asesor','POST','/api/acceso/ingresar',200,['email'=>'asesor-qa@example.test','password'=>$password])['token'] ?? null;
    api('API rechaza contraseña equivocada','POST','/api/acceso/ingresar',401,['email'=>'admin-qa@example.test','password'=>'incorrecta']);
    $registration = ['nombres'=>'Viajero','apellidos'=>'Pruebas','email'=>'cliente-'.$run.'@example.test','password'=>$password];
    $client = api('API registra cliente','POST','/api/acceso/registrar',200,$registration)['token'] ?? null;
    $registration['email'] = 'otro-'.$run.'@example.test';
    $other = api('API registra segundo cliente','POST','/api/acceso/registrar',200,$registration)['token'] ?? null;
    api('API rechaza registro duplicado','POST','/api/acceso/registrar',409,$registration);
    foreach (['ADMIN'=>$admin,'ADMINISTRATIVO'=>$staff,'CLIENTE'=>$client] as $rol=>$token) {
        $identity = api('API verifica sesión '.$rol,'GET','/api/acceso/sesion',200,null,$token);
        check('API rol correcto '.$rol, ($identity['rol'] ?? null) === $rol);
    }
    api('API cliente no accede a usuarios','GET','/api/v1/usuarios',403,null,$client);
    api('API cliente no accede a atención','GET','/api/atencion/panel',403,null,$client);
    api('API asesor no crea administradores','POST','/api/v1/usuarios',403,[],$staff);
    api('API admin no entra al perfil del cliente','GET','/api/cliente/perfil',403,null,$admin);
    $users = api('API lista usuarios','GET','/api/v1/usuarios',200,null,$admin);
    check('API nunca expone hashes de contraseña', !str_contains(json_encode($users),'passwordHash') && !str_contains(json_encode($users),'pbkdf2'));
    api('API conexión MySQL real','GET','/api/v1/health/db',200,null,$admin);
});

section('Inventario', function () use (&$product,&$unit,&$admin,$date,$run) {
    api('API catálogos de productos','GET','/api/registro-productos/catalogos',200,null,$admin);
    api('API catálogos de inventario','GET','/api/inventario/catalogos',200,null,$admin);
    $data = ['nombre'=>'Carpa QA '.$run,'codClase'=>'CL-004','codMarca'=>'M-0001','codUm'=>'UM-001','modelo'=>'QA','color'=>'Verde'];
    $product = api('API crea producto','POST','/api/registro-productos',201,$data,$admin)['codigo'] ?? throw new RuntimeException('No se creó producto');
    api('API consulta producto','GET','/api/inventario/productos/'.$product,200,null,$admin);
    api('API edita producto','PUT','/api/inventario/productos/'.$product,200,[...$data,'stockMinimo'=>2,'estado'=>'ACTIVO'],$admin);
    $data = ['codigoUnico'=>'QA-'.$run,'codAlmacen'=>'ALM-001','estadoFisico'=>'NUEVO','calificacion'=>10,'fechaAlta'=>$date,'requiereArreglo'=>false,'requiereCambio'=>false,'requiereBaja'=>false,'observaciones'=>'Unidad sintética'];
    api('API registra unidad','POST','/api/inventario/productos/'.$product.'/unidades',201,$data,$admin);
    api('API rechaza código de unidad duplicado','POST','/api/inventario/productos/'.$product.'/unidades',409,$data,$admin);
    $ficha = api('API lista unidades','GET','/api/inventario/productos/'.$product.'/unidades',200,null,$admin);
    $unit = $ficha['unidades'][0]['id'] ?? throw new RuntimeException('No aparece la unidad creada');
    api('API edita condición de unidad','PUT','/api/inventario/productos/'.$product.'/unidades/'.$unit,200,[...$data,'calificacion'=>8,'requiereArreglo'=>true],$admin);
    api('API rechaza calificación inválida','PUT','/api/inventario/productos/'.$product.'/unidades/'.$unit,422,[...$data,'calificacion'=>11],$admin);
    api('API impide cambiar código histórico','PUT','/api/inventario/productos/'.$product.'/unidades/'.$unit,422,[...$data,'codigoUnico'=>'OTRO'],$admin);
    api('API impide cambiar unidad de medida con inventario','PUT','/api/inventario/productos/'.$product,422,['nombre'=>'Carpa QA','codClase'=>'CL-004','codUm'=>'UM-002','estado'=>'ACTIVO'],$admin);
    api('API resumen de inventario','GET','/api/inventario/productos?estado=TODOS',200,null,$admin);
    api('API documentos, existencias, préstamos y movimientos','GET','/api/inventario/operaciones',200,null,$admin);
    api('API clasificación ABC','GET','/api/abc',200,null,$admin);
    $eoq = ['demandaAnual'=>1000,'costoPedido'=>50,'costoMantenimientoAnual'=>10,'costoUnitario'=>20,'plazoEntregaDias'=>5,'diasAnuales'=>365];
    $r = api('API cálculo EOQ','POST','/api/io/eoq',200,$eoq,$admin);
    check('API fórmula EOQ correcta', abs(($r['loteTeorico'] ?? 0)-100)<0.0001);
    api('API EOQ rechaza demanda cero','POST','/api/io/eoq',422,[...$eoq,'demandaAnual'=>0],$admin);
});

section('Catálogos y gestión', function () use ($admin,$date,$start,$run) {
    foreach (['personas','empleados','clientes','usuarios','roles','cargos','departamentos','hoteles','habitaciones','tarifas-hotel','vehiculos','conductores','servicios-transporte'] as $module) {
        api('API lista '.$module,'GET','/api/v1/'.$module,200,null,$admin);
    }
    foreach (['cargos'=>['desCargo'=>'Cargo QA','descripcion'=>'Prueba'],'departamentos'=>['desDep'=>'Departamento QA','descripcion'=>'Prueba'],'personas'=>['nombres'=>'Persona','apellidos'=>'CRUD','numeroDocumento'=>'QA-CRUD']] as $module=>$data) {
        $key = ['cargos'=>'codCargo','departamentos'=>'codDep','personas'=>'codPersona'][$module];
        $r = api('API crea '.$module,'POST','/api/v1/'.$module,201,$data,$admin);
        $id = $r[$key] ?? throw new RuntimeException('No se creó '.$module);
        api('API consulta '.$module,'GET','/api/v1/'.$module.'/'.$id,200,null,$admin);
        api('API actualiza '.$module,'PUT','/api/v1/'.$module.'/'.$id,200,$data,$admin);
        api('API elimina '.$module,'DELETE','/api/v1/'.$module.'/'.$id,204,null,$admin);
        api('API confirma eliminación '.$module,'GET','/api/v1/'.$module.'/'.$id,404,null,$admin);
    }
    $hotel = ['nombre'=>'Hotel QA','categoria'=>3,'ciudad'=>'La Paz','pais'=>'Bolivia','direccion'=>'Dirección QA','estado'=>'ACTIVO'];
    $h = api('API crea hotel','POST','/api/v1/hoteles',201,$hotel,$admin)['codigo'];
    api('API actualiza hotel','PUT','/api/v1/hoteles/'.$h,200,[...$hotel,'nombre'=>'Hotel QA actualizado'],$admin);
    $room = ['codHotel'=>$h,'nombre'=>'Doble QA','capacidad'=>2,'numCamas'=>1,'estado'=>'ACTIVO'];
    $roomId = api('API crea habitación','POST','/api/v1/habitaciones',201,$room,$admin)['codigo'];
    $rate = ['codTipoHab'=>$roomId,'fechaInicio'=>$date,'moneda'=>'BOB','precioNoche'=>250,'incluyeDesayuno'=>true];
    api('API crea tarifa hotel','POST','/api/v1/tarifas-hotel',201,$rate,$admin);
    api('API rechaza tarifas solapadas','POST','/api/v1/tarifas-hotel',409,$rate,$admin);
    $vehicle = ['placa'=>'QA-'.$run,'marca'=>'Toyota','modelo'=>'Hiace','capacidad'=>12,'tipo'=>'Minibus','estado'=>'DISPONIBLE'];
    api('API crea vehículo','POST','/api/v1/vehiculos',201,$vehicle,$admin);
    api('API rechaza placa duplicada','POST','/api/v1/vehiculos',409,$vehicle,$admin);
    $service = ['nombre'=>'Traslado QA','tipo'=>'Privado','origen'=>'La Paz','destino'=>'Copacabana','precioBase'=>200,'moneda'=>'BOB','capacidadMax'=>12,'estado'=>'ACTIVO'];
    api('API crea servicio de transporte','POST','/api/v1/servicios-transporte',201,$service,$admin);
    api('API catálogo público de hospedaje','GET','/api/publico/hospedaje');
    api('API catálogo público de transporte','GET','/api/publico/transporte');
});

section('Viaje completo', function () use (&$solicitud,&$propuesta,&$reserva,$client,$other,$admin,$staff,$date,$start,$end,$run) {
    api('API perfil inicial','GET','/api/cliente/perfil',200,null,$client);
    $trip = ['fechaInicio'=>$start,'fechaFin'=>$end,'personas'=>2,'presupuesto'=>1500,'moneda'=>'BOB','destinos'=>'Uyuni','intereses'=>'Paisajes y cultura','hotelCodigo'=>'HOT-UYU','habitacionCodigo'=>'HAB-UYU-DBL','transporteCodigo'=>'TRS-LPZ-AER'];
    api('API impide solicitar sin perfil completo','POST','/api/cliente/solicitudes',409,$trip,$client);
    $profile = ['nombres'=>'Viajero','apellidos'=>'Pruebas','telefono'=>'70000000','direccion'=>'La Paz','tipoDocumento'=>'CI','numeroDocumento'=>'QA-VIAJERO-'.$run,'fechaNacimiento'=>'1990-01-01'];
    $p = api('API completa perfil','PUT','/api/cliente/perfil',200,$profile,$client);
    check('API perfil completo al 100 %', ($p['completitud']['completo'] ?? false) === true);
    // Se consulta el código vigente del catálogo, sin asumir datos comerciales.
    $transport = api('API opciones de traslado','GET','/api/publico/transporte');
    $trip['transporteCodigo'] = $transport['servicios'][0]['codigo'] ?? null;
    $solicitud = api('API cliente solicita viaje','POST','/api/cliente/solicitudes',200,$trip,$client)['codigo'] ?? throw new RuntimeException('No se creó solicitud');
    api('API asesor ve la solicitud asignada','GET','/api/atencion/solicitudes/'.$solicitud,200,null,$staff);
    api('API cliente envía mensaje','POST','/api/cliente/mensajes',200,['contenido'=>'Consulta de prueba'],$client);
    $panel = api('API panel de atención','GET','/api/atencion/panel',200,null,$admin);
    $conversation = array_values(array_filter($panel['conversaciones'], static fn ($c) => $c['email'] === 'cliente-'.$run.'@example.test'))[0]['codigo'];
    api('API asesor responde','POST','/api/atencion/conversaciones/'.$conversation.'/mensajes',204,['contenido'=>'Respuesta de prueba'],$admin);
    $msgs = api('API cliente recibe mensajes','GET','/api/cliente/mensajes',200,null,$client);
    check('API chat conserva la respuesta', str_contains(json_encode($msgs),'Respuesta de prueba'));
    $proposal = ['solicitud'=>$solicitud,'titulo'=>'Uyuni QA','descripcion'=>'Viaje de prueba','precio'=>1000,'moneda'=>'BOB','dias'=>[['dia'=>1,'descripcion'=>'Llegada','servicios'=>'Traslado'],['dia'=>2,'descripcion'=>'Excursión','servicios'=>'Guía']]];
    $propuesta = api('API asesor envía propuesta','POST','/api/atencion/propuestas',200,$proposal,$staff)['codigo'] ?? throw new RuntimeException('No se creó propuesta');
    api('API cliente consulta propuesta','GET','/api/cliente/propuestas/'.$propuesta,200,null,$client);
    api('API impide que otro cliente lea la propuesta','GET','/api/cliente/propuestas/'.$propuesta,404,null,$other);
    api('API cliente comunica aceptación','POST','/api/cliente/propuestas/'.$propuesta.'/aceptar',200,[],$client);
    $reserva = api('API personal registra aceptación','POST','/api/atencion/propuestas/'.$propuesta.'/aceptacion',200,[],$staff)['reserva'] ?? throw new RuntimeException('No se creó reserva');
    api('API impide doble aceptación','POST','/api/atencion/propuestas/'.$propuesta.'/aceptacion',409,[],$staff);
    $pay = ['fecha'=>$date,'monto'=>500,'metodo'=>'Transferencia','referencia'=>'QA-ANTICIPO','verificado'=>true];
    api('API rechaza anticipo menor al 50 %','POST','/api/atencion/reservas/'.$reserva.'/pagos',422,[...$pay,'monto'=>100],$staff);
    api('API registra anticipo del 50 %','POST','/api/atencion/reservas/'.$reserva.'/pagos',200,$pay,$staff);
    api('API impide duplicar pago','POST','/api/atencion/reservas/'.$reserva.'/pagos',409,$pay,$staff);
    $r = api('API consulta reserva confirmada','GET','/api/atencion/reservas/'.$reserva,200,null,$staff);
    check('API reserva confirmada y pagado correcto', ($r['estado'] ?? null)==='CONFIRMADA' && (float)($r['pagado'] ?? 0)===500.0);
    api('API rechaza pago superior al saldo','POST','/api/atencion/reservas/'.$reserva.'/pagos',422,[...$pay,'monto'=>501,'referencia'=>'QA-EXCESO'],$staff);
    $calendar = api('API calendario muestra viaje','GET','/api/atencion/calendario?desde='.$start.'&hasta='.$end,200,null,$staff);
    check('API reserva aparece en calendario', str_contains(json_encode($calendar),$reserva));
    $notes = api('API notificaciones del cliente','GET','/api/cliente/notificaciones',200,null,$client);
    if ($notes) { api('API marca notificación leída','POST','/api/cliente/notificaciones/'.$notes[0]['codigo'].'/leer',204,[],$client); }
    api('API impide cancelar sin solicitud','POST','/api/atencion/reservas/'.$reserva.'/cancelacion',409,[],$staff);
    api('API cliente solicita cancelación','POST','/api/cliente/reservas/'.$reserva.'/cancelacion',200,['contenido'=>'Cancelación sintética de prueba'],$client);
    api('API personal registra cancelación','POST','/api/atencion/reservas/'.$reserva.'/cancelacion',204,[],$staff);
    $r = api('API verifica reserva cancelada','GET','/api/atencion/reservas/'.$reserva,200,null,$staff);
    check('API cancelación conserva pagos', ($r['estado'] ?? null)==='CANCELADA' && (float)($r['pagado'] ?? 0)===500.0);
    api('API impide pagar reserva cancelada','POST','/api/atencion/reservas/'.$reserva.'/pagos',409,[...$pay,'referencia'=>'QA-CANCELADO'],$staff);
});

section('Gestión relacionada y auditoría', function () use ($admin,$staff,$password,$run,$date,$start) {
    $person = ['nombres'=>'Conductor','apellidos'=>'QA','numeroDocumento'=>'QA-CON-'.$run];
    $id = api('API crea persona para empleado','POST','/api/v1/personas',201,$person,$admin)['codPersona'];
    $employee = ['codPersona'=>$id,'codCargo'=>'CAR-005','codDep'=>'DEP-QA','fechaIngreso'=>$date,'estado'=>'ACTIVO','esGuia'=>false,'esAsesor'=>false,'esConductor'=>true];
    $employeeId = api('API crea empleado','POST','/api/v1/empleados',201,$employee,$admin)['codEmpleado'];
    api('API actualiza empleado','PUT','/api/v1/empleados/'.$employeeId,200,[...$employee,'salario'=>3000],$admin);
    api('API impide eliminar persona vinculada','DELETE','/api/v1/personas/'.$id,409,null,$admin);
    $driver = ['codEmpleado'=>$employeeId,'licencia'=>'QA-LIC-'.$run,'categoriaLic'=>'C','vencimientoLic'=>$start,'experienciaAnios'=>5,'estado'=>'ACTIVO'];
    $driverId = api('API crea conductor','POST','/api/v1/conductores',201,$driver,$admin)['codigo'];
    api('API actualiza conductor','PUT','/api/v1/conductores/'.$driverId,200,[...$driver,'experienciaAnios'=>6],$admin);
    api('API rechaza conductor con licencia vencida','PUT','/api/v1/conductores/'.$driverId,422,[...$driver,'vencimientoLic'=>'2000-01-01'],$admin);
    $person['numeroDocumento'] = 'QA-CLI-'.$run;
    $personId = api('API crea persona para cliente','POST','/api/v1/personas',201,$person,$admin)['codPersona'];
    $customer = ['codPersona'=>$personId,'codigoCliente'=>'QA-'.$run,'estado'=>'ACTIVO'];
    $customerId = api('API crea ficha de cliente','POST','/api/v1/clientes',201,$customer,$admin)['codCliente'];
    api('API actualiza ficha de cliente','PUT','/api/v1/clientes/'.$customerId,200,[...$customer,'estado'=>'INACTIVO'],$admin);
    api('API elimina ficha sin historial','DELETE','/api/v1/clientes/'.$customerId,204,null,$admin);
    api('API elimina persona sin vínculos','DELETE','/api/v1/personas/'.$personId,204,null,$admin);
    $user = ['username'=>'usuario-qa-'.$run,'email'=>'crud-'.$run.'@example.test','password'=>$password,'codRol'=>'ROL-003','activo'=>true];
    $userId = api('API admin crea cuenta','POST','/api/v1/usuarios',201,$user,$admin)['idUsuario'];
    api('API admin desactiva cuenta','PUT','/api/v1/usuarios/'.$userId,200,[...$user,'activo'=>false],$admin);
    api('API cuenta inactiva no ingresa','POST','/api/acceso/ingresar',401,['email'=>$user['email'],'password'=>$password]);
    api('API admin elimina cuenta sin sesiones','DELETE','/api/v1/usuarios/'.$userId,204,null,$admin);
    $users = api('API consulta administrador protegido','GET','/api/v1/usuarios',200,null,$admin);
    $lastAdmin = array_values(array_filter($users, static fn ($u) => $u['email']==='admin-qa@example.test'))[0];
    api('API impide eliminar al último administrador','DELETE','/api/v1/usuarios/'.$lastAdmin['idUsuario'],409,null,$admin);
    api('API impide degradar al último administrador','PUT','/api/v1/usuarios/'.$lastAdmin['idUsuario'],409,['username'=>'admin-qa','email'=>'admin-qa@example.test','codRol'=>'ROL-002','activo'=>true],$admin);
    api('API no permite crear roles adicionales','POST','/api/v1/roles',405,['desRol'=>'OTRO'],$admin);
    api('API no permite eliminar roles del sistema','DELETE','/api/v1/roles/ROL-003',405,null,$admin);
    api('API asesor no puede modificar roles','PUT','/api/v1/roles/ROL-003',403,['desRol'=>'CLIENTE'],$staff);
    foreach (['almacenes'=>['nombre'=>'Almacén QA '.$run,'ubicacion'=>'La Paz','estado'=>'ACTIVO'],
        'clases'=>['nombre'=>'Clase QA '.$run,'categoria'=>'ALPINISMO','serializado'=>true,'consumible'=>false],
        'marcas'=>['nombre'=>'Marca QA '.$run,'pais'=>'Bolivia'],
        'medidas'=>['nombre'=>'Medida QA '.$run,'abreviatura'=>'qa']] as $type=>$data) {
        $id = api('API crea catálogo de inventario '.$type,'POST','/api/inventario/catalogos/'.$type,200,$data,$admin)['codigo'];
        api('API edita catálogo de inventario '.$type,'PUT','/api/inventario/catalogos/'.$type.'/'.$id,200,$data,$admin);
        api('API rechaza catálogo duplicado '.$type,'POST','/api/inventario/catalogos/'.$type,409,$data,$admin);
    }
});

section('Web autenticada real', function () use ($password,$run,$product,$unit,$solicitud,$propuesta,$reserva) {
    webLogin('admin','admin-qa@example.test',$password);
    foreach (['/admin','/admin/gestion','/inventario','/inventario/abc','/inventario/catalogos','/inventario/operaciones','/investigacion-operativa','/atencion','/atencion/calendario','/atencion/catalogo'] as $path) {
        request('Web admin '.$path,'GET',$path,200,null,null,'admin');
    }
    foreach (['personas','empleados','clientes','usuarios','roles','cargos','departamentos','hoteles','habitaciones','tarifas-hotel','vehiculos','conductores','servicios-transporte'] as $module) {
        request('Web gestión '.$module,'GET','/'.$module,200,null,null,'admin');
        if ($module !== 'roles') { request('Web formulario '.$module,'GET','/'.$module.'/nuevo',200,null,null,'admin'); }
    }
    foreach (['existencias','referencias','documentos','precios','prestamos','movimientos'] as $view) {
        request('Web inventario sección '.$view,'GET','/inventario/operaciones?ver='.$view,200,null,null,'admin');
    }
    if ($product && $unit) {
        foreach (['/inventario/productos/'.$product,'/inventario/productos/'.$product.'/editar','/inventario/'.$product.'/unidades','/inventario/'.$product.'/unidades/'.$unit.'/editar','/inventario/'.$product.'/reposicion'] as $path) {
            request('Web detalle de inventario '.$path,'GET',$path,200,null,null,'admin');
        }
    }
    if ($solicitud) { request('Web solicitud de atención','GET','/atencion/solicitudes/'.$solicitud,200,null,null,'admin'); }
    if ($reserva) { request('Web detalle de reserva','GET','/atencion/reservas/'.$reserva,200,null,null,'admin'); }
    $page = request('Web formulario CRUD real','GET','/cargos/nuevo',200,null,null,'admin');
    $r = request('Web guarda cargo a través de Quarkus','POST','/cargos',302,['_token'=>csrf($page['html']),'desCargo'=>'Cargo desde Laravel '.$run,'descripcion'=>'Integración HTTP real'],null,'admin');
    $path = parse_url($r['headers']['location'] ?? '', PHP_URL_PATH) ?? '';
    check('Web CRUD redirige a detalle', (bool)preg_match('#^/cargos/[^/]+$#',$path));
    if (preg_match('#^/cargos/[^/]+$#',$path)) {
        $page = request('Web muestra cargo recién creado','GET',$path,200,null,null,'admin');
        check('Web confirma persistencia del cargo',str_contains($page['html'],'Cargo desde Laravel '.$run));
        $page = request('Web formulario de edición de cargo','GET',$path.'/editar',200,null,null,'admin');
        request('Web actualiza cargo a través de Quarkus','POST',$path,302,['_token'=>csrf($page['html']),'_method'=>'PUT','desCargo'=>'Cargo actualizado '.$run,'descripcion'=>'Prueba'],null,'admin');
        $page = request('Web confirma edición persistida','GET',$path,200,null,null,'admin');
        check('Web muestra nombre actualizado',str_contains($page['html'],'Cargo actualizado '.$run));
        $page = request('Web confirmación de eliminación','GET',$path.'/eliminar',200,null,null,'admin');
        request('Web elimina cargo a través de Quarkus','POST',$path,302,['_token'=>csrf($page['html']),'_method'=>'DELETE','confirmar'=>'1'],null,'admin');
        request('Web confirma eliminación persistida','GET',$path,404,null,null,'admin');
    }
    webLogin('cliente','cliente-'.$run.'@example.test',$password);
    foreach (['/mi-viaje','/mi-viaje/perfil','/mi-viaje/solicitar','/mi-viaje/mensajes','/mi-viaje/notificaciones','/alojamientos','/traslados'] as $path) {
        request('Web cliente '.$path,'GET',$path,200,null,null,'cliente');
    }
    if ($propuesta) { request('Web cliente ve propuesta','GET','/mi-viaje/propuestas/'.$propuesta,200,null,null,'cliente'); }
    request('Web cliente no entra al panel','GET','/admin',403,null,null,'cliente');
    request('Web admin no entra al portal del cliente','GET','/mi-viaje',403,null,null,'admin');
    $page = request('Web obtiene CSRF para salir','GET','/mi-viaje/perfil',200,null,null,'cliente');
    request('Web cierra sesión','POST','/salir',302,['_token'=>csrf($page['html'])],null,'cliente');
    request('Web sesión cerrada no accede al portal','GET','/mi-viaje',302,null,null,'cliente');
});

section('Revocación de tokens', function () use ($other) {
    api('API cierra sesión','POST','/api/acceso/salir',204,[],$other);
    api('API token revocado no funciona','GET','/api/acceso/sesion',401,null,$other);
});

section('Límites de acceso', function () use ($run) {
    for ($i=1;$i<=9;$i++) {
        api('API límite de intentos de acceso '.$i,'POST','/api/acceso/ingresar',$i<=8?401:429,
            ['email'=>'limite-'.$run.'@example.test','password'=>'incorrecta']);
    }
    $page = request('Web obtiene formulario para prueba de límite','GET','/registro',200,null,null,'limite');
    $token = csrf($page['html']);
    for ($i=1;$i<=6;$i++) {
        request('Web límite de intentos de registro '.$i,'POST','/registro',$i<=5?302:429,
            ['_token'=>$token,'email'=>'invalido'],null,'limite');
    }
});

$failures = array_values(array_filter($results, static fn ($r) => !$r['passed']));
$summary = ['executed_at'=>date(DATE_ATOM),'tests'=>count($results),'passed'=>count($results)-count($failures),'failed'=>count($failures),'failures'=>$failures,'results'=>$results];
file_put_contents(__DIR__.'/integracion-http-resultados.json', json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo json_encode(['tests'=>$summary['tests'],'passed'=>$summary['passed'],'failed'=>$summary['failed']],JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($failures ? 1 : 0);
