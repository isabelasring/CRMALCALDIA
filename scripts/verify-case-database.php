<?php

/**
 * Muestra columnas custom en case, contact y tablas c_* del proyecto.
 *
 * docker cp scripts/verify-case-database.php espocrm:/tmp/
 * docker exec espocrm php /tmp/verify-case-database.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$pdo = $app->getContainer()->getByClass(EntityManager::class)->getPDO();

echo "=== Tabla CASE (casos) — columnas c_* ===\n";
$stmt = $pdo->query(
    "SELECT column_name FROM information_schema.columns
     WHERE table_name = 'case' AND column_name LIKE 'c\\_%' ESCAPE '\\'
     ORDER BY 1"
);
$caseCols = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($caseCols === []) {
    echo "  (ninguna) — OK, tabla case limpia.\n";
} else {
    foreach ($caseCols as $col) {
        echo "  - {$col}\n";
    }
}

echo "\n=== Tabla CONTACT — columnas c_* (otra entidad) ===\n";
$stmt = $pdo->query(
    "SELECT column_name FROM information_schema.columns
     WHERE table_name = 'contact' AND column_name LIKE 'c\\_%' ESCAPE '\\'
     ORDER BY 1"
);
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $col) {
    echo "  - {$col}\n";
}

echo "\n=== Tablas separadas con prefijo c_ (entidades custom Espo) ===\n";
$stmt = $pdo->query(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema = 'public' AND table_name LIKE 'c\\_%' ESCAPE '\\'
     ORDER BY 1"
);
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
    echo "  - {$t}\n";
}

echo "\n=== Campos Case en metadata Espo (no es BD, es configuración) ===\n";
$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);
foreach (array_keys($metadata->get(['entityDefs', 'Case', 'fields']) ?? []) as $field) {
    $defs = $metadata->get(['entityDefs', 'Case', 'fields', $field]) ?? [];
    if (!empty($defs['isCustom'])) {
        echo "  - {$field}\n";
    }
}

echo "\nListo.\n";
