<?php
require_once '/var/www/html/bootstrap.php';
$pdo = (new Espo\Core\Application())->getContainer()->getByClass(Espo\ORM\EntityManager::class)->getPDO();
foreach (['c_tipo_de_documento', 'c_canal_de_reporte', 'c_municipio'] as $col) {
    $rows = $pdo->query("SELECT DISTINCT \"$col\" FROM \"case\" WHERE deleted = false AND \"$col\" IS NOT NULL AND \"$col\" != '' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
    echo "$col: " . json_encode($rows, JSON_UNESCAPED_UNICODE) . "\n";
}
