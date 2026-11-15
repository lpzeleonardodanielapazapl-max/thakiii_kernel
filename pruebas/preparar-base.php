<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$config = parse_ini_file($root.'/thaki-voyage-backend/.env', false, INI_SCANNER_RAW);
$dsn = 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4';
$db = new PDO($dsn, $config['THAKI_DB_USER'] ?? 'root', $config['THAKI_DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = 'thaki_revision_'.date('Ymd_His');
if (!preg_match('/^thaki_revision_\d{8}_\d{6}$/D', $name)) {
    throw new RuntimeException('Nombre de base de pruebas inválido.');
}
$sql = file_get_contents($root.'/thaki.sql');
$sql = preg_replace('/^DROP DATABASE IF EXISTS thaki_modificado;\R/m', '', $sql);
$sql = str_replace('thaki_modificado', $name, $sql);
$db->exec($sql);
$db->exec("INSERT INTO departamento (cod_dep,des_dep) VALUES ('DEP-QA','Área de pruebas')");
$password = 'Revision-'.bin2hex(random_bytes(12));
$salt = random_bytes(16);
$hash = 'pbkdf2-sha256$600000$'.base64_encode($salt).'$'.base64_encode(hash_pbkdf2('sha256', $password, $salt, 600000, 32, true));
$insert = $db->prepare('INSERT INTO usuario (username,email,password_hash,cod_rol,activo,email_verificado,created_at) VALUES (?,?,?,?,1,1,CURRENT_TIMESTAMP)');
$insert->execute(['admin-qa','admin-qa@example.test',$hash,'ROL-001']);
$insert->execute(['asesor-qa','asesor-qa@example.test',$hash,'ROL-002']);
$db->exec("INSERT INTO persona (cod_persona,nombres,apellidos,email) VALUES ('PER-QA-ASESOR','Asesor','Pruebas','asesor-qa@example.test')");
$db->exec("INSERT INTO empleado (cod_empleado,cod_persona,cod_cargo,cod_dep,fecha_ingreso,estado,es_asesor) VALUES ('E-QA-ASESOR','PER-QA-ASESOR','CAR-003','DEP-QA',CURRENT_DATE,'ACTIVO',1)");
$meta = [
    'database' => $name,
    'created_at' => date(DATE_ATOM),
    'tables' => (int) $db->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn(),
    'original_tables' => (int) $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='thaki_modificado'")->fetchColumn(),
    'mysql_version' => $db->query('SELECT VERSION()')->fetchColumn(),
];
file_put_contents(__DIR__.'/entorno.json', json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
// Solo las cuentas sintéticas de esta base usan esta clave temporal.
file_put_contents(__DIR__.'/.credenciales-qa.json', json_encode(['password' => $password]));
echo json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
