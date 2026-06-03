<?php
require_once '/var/www/html/bootstrap.php';
$app = new Espo\Core\Application();
$app->setupSystemUser();
$pdo = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class)->getPDO();
$tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname='public' AND tablename LIKE '%field%'")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $tables) . "\n";
foreach ($tables as $t) {
    $stmt = $pdo->query('SELECT * FROM "' . $t . '" LIMIT 3');
    $cols = array_keys($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
    if ($cols) echo "$t cols: " . implode(',', $cols) . "\n";
}
