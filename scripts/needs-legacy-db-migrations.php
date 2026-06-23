<?php

/**
 * Indica si hace falta correr migraciones legacy de columnas (BD ya usada).
 * Exit 0 = sí migrar. Exit 1 = instalación nueva, omitir migraciones.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$pdo = $em->getPDO();

$legacyColumns = ['c_cedula', 'c_categoria', 'c_tipo', 'c_nombre_del_peticionario'];

foreach ($legacyColumns as $column) {
    $exists = (bool) $pdo->query("
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'case' AND column_name = " . $pdo->quote($column) . "
    ")->fetchColumn();

    if ($exists) {
        echo "Columna legacy {$column} encontrada — se ejecutarán migraciones.\n";
        exit(0);
    }
}

echo "BD nueva — se omiten migraciones legacy.\n";
exit(1);
