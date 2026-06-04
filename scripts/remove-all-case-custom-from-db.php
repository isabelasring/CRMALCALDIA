<?php

/**
 * Fuerza eliminación de columnas c_* en case + muestra resultado.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$pdo = $app->getContainer()->getByClass(EntityManager::class)->getPDO();

$stmt = $pdo->query(
    "SELECT column_name FROM information_schema.columns
     WHERE table_name = 'case' AND column_name LIKE 'c\\_%' ESCAPE '\\'"
);
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($columns as $column) {
    $pdo->exec('ALTER TABLE "case" DROP COLUMN IF EXISTS "' . str_replace('"', '', $column) . '"');
    echo "Eliminada columna case.{$column}\n";
}

if ($columns === []) {
    echo "La tabla case ya no tenía columnas c_*.\n";
}

$app->getContainer()->getByClass(DataManager::class)->rebuild();
$app->getContainer()->getByClass(Espo\Core\Utils\Config::class);
shell_exec('php command.php clear-cache 2>/dev/null');

echo "Rebuild + cache OK.\n";
