<?php

/**
 * Elimina columnas custom (c_*) de la tabla case en PostgreSQL.
 *
 * docker cp scripts/remove-case-custom-fields.php espocrm:/tmp/
 * docker exec espocrm php /tmp/remove-case-custom-fields.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$pdo = $em->getPDO();

$stmt = $pdo->query(
    "SELECT column_name FROM information_schema.columns
     WHERE table_name = 'case' AND column_name LIKE 'c\\_%' ESCAPE '\\'
     ORDER BY column_name"
);

$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($columns === []) {
    echo "No hay columnas c_* en la tabla case.\n";
    exit(0);
}

echo 'Eliminando ' . count($columns) . " columnas:\n";

foreach ($columns as $column) {
    $sql = 'ALTER TABLE "case" DROP COLUMN IF EXISTS "' . str_replace('"', '', $column) . '"';
    $pdo->exec($sql);
    echo "  OK {$column}\n";
}

$app->getContainer()->getByClass(DataManager::class)->rebuild();
echo "Rebuild completado.\n";
