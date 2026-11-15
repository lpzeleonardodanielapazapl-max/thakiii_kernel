<?php

declare(strict_types=1);

$config = parse_ini_file(dirname(__DIR__).'/thaki-voyage-backend/.env', false, INI_SCANNER_RAW);
$meta = json_decode(file_get_contents(__DIR__.'/entorno.json'), true, 512, JSON_THROW_ON_ERROR);
$db = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $config['THAKI_DB_USER'] ?? 'root', $config['THAKI_DB_PASSWORD'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('SET SESSION TRANSACTION READ ONLY');
$db->beginTransaction();
$results = [];
foreach (['thaki_modificado', $meta['database']] as $schema) {
    if (!preg_match('/^[a-z0-9_]+$/D', $schema)) { throw new RuntimeException('Esquema inválido.'); }
    $query = $db->prepare('SELECT CONSTRAINT_NAME,TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION');
    $query->execute([$schema]);
    $groups = [];
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) { $groups[$row['TABLE_NAME'].'.'.$row['CONSTRAINT_NAME']][]=$row; }
    foreach ($groups as $key=>$columns) {
        $first = $columns[0];
        $join = $nonnull = [];
        foreach ($columns as $column) {
            $join[] = 'c.`'.$column['COLUMN_NAME'].'`=p.`'.$column['REFERENCED_COLUMN_NAME'].'`';
            $nonnull[] = 'c.`'.$column['COLUMN_NAME'].'` IS NOT NULL';
        }
        $sql = 'SELECT COUNT(*) FROM `'.$schema.'`.`'.$first['TABLE_NAME'].'` c LEFT JOIN `'.$schema.'`.`'.$first['REFERENCED_TABLE_NAME'].'` p ON '.implode(' AND ',$join).' WHERE '.implode(' AND ',$nonnull).' AND p.`'.$first['REFERENCED_COLUMN_NAME'].'` IS NULL';
        $orphans = (int)$db->query($sql)->fetchColumn();
        $results[]=['database'=>$schema,'check'=>$key,'passed'=>$orphans===0,'orphan_rows'=>$orphans];
    }
}
$qa = $meta['database'];
$checks = [
    'Tokens guardados exclusivamente como hashes SHA-256' => "SELECT COUNT(*) FROM `$qa`.sesion WHERE id_sesion NOT REGEXP '^[0-9a-f]{64}$'",
    'Importes válidos en todos los pagos sintéticos' => "SELECT COUNT(*) FROM `$qa`.pago WHERE monto<=0 OR estado<>'CONFIRMADO'",
    'Los pagos rechazados no incrementaron el total de reservas' => "SELECT COUNT(*) FROM `$qa`.reserva r JOIN (SELECT cod_reserva,SUM(monto) pagado FROM `$qa`.pago GROUP BY cod_reserva) p ON p.cod_reserva=r.cod_reserva WHERE p.pagado<>500",
    'Cada reserva del recorrido terminó cancelada' => "SELECT COUNT(*) FROM `$qa`.reserva WHERE estado<>'CANCELADA'",
];
foreach ($checks as $label=>$sql) {
    $bad = (int)$db->query($sql)->fetchColumn();
    $results[]=['database'=>$qa,'check'=>$label,'passed'=>$bad===0,'invalid_rows'=>$bad];
}
$audit=(int)$db->query("SELECT COUNT(*) FROM `$qa`.auditoria")->fetchColumn();
$results[]=['database'=>$qa,'check'=>'Los catálogos producen auditoría persistida','passed'=>$audit>=8,'audit_events'=>$audit];
$db->rollBack();
$failed = count(array_filter($results,static fn($r)=>!$r['passed']));
$summary=['checks'=>count($results),'passed'=>count($results)-$failed,'failed'=>$failed,'results'=>$results];
file_put_contents(__DIR__.'/integridad-mysql.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(['checks'=>$summary['checks'],'passed'=>$summary['passed'],'failed'=>$failed]).PHP_EOL;
exit($failed?1:0);
