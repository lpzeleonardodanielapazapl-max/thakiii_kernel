<?php

declare(strict_types=1);

$meta = json_decode(file_get_contents(__DIR__.'/entorno.json'), true, 512, JSON_THROW_ON_ERROR);
$name = $meta['database'];
if (($argv[1] ?? '') !== '--confirmar-base-pruebas' || !preg_match('/^thaki_revision_\d{8}_\d{6}$/D', $name)) {
    throw new RuntimeException('Se requiere identificar explícitamente una base temporal de esta revisión.');
}
$config = parse_ini_file(dirname(__DIR__).'/thaki-voyage-backend/.env', false, INI_SCANNER_RAW);
$db = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $config['THAKI_DB_USER'] ?? 'root', $config['THAKI_DB_PASSWORD'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('DROP DATABASE `'.$name.'`');
foreach (['.credenciales-qa.json','cookies-admin.txt','cookies-cliente.txt','cookies-limite.txt'] as $file) {
    if (is_file(__DIR__.'/'.$file)) { unlink(__DIR__.'/'.$file); }
}
$original = (int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='thaki_modificado'")->fetchColumn();
$clean = ['test_database_removed'=>$name,'temporary_credentials_removed'=>true,'original_database_tables'=>$original,'finished_at'=>date(DATE_ATOM)];
file_put_contents(__DIR__.'/limpieza.json',json_encode($clean,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode($clean).PHP_EOL;
