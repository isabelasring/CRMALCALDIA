<?php
require_once '/var/www/html/bootstrap.php';
$pdo = (new Espo\Core\Application())->getContainer()->getByClass(Espo\ORM\EntityManager::class)->getPDO();
$cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'case' AND column_name LIKE 'c%' ORDER BY column_name")->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $cols) . "\n";
