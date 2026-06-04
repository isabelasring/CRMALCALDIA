<?php

/**
 * Layouts vacíos + quita columna custom c_fecha_vencimiento si existe.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$pdo = $app->getContainer()->getByClass(EntityManager::class)->getPDO();

$pdo->exec('ALTER TABLE "case" DROP COLUMN IF EXISTS c_fecha_vencimiento');

$app->getContainer()->getByClass(DataManager::class)->rebuild();
echo "Layouts vacíos desplegados, columna c_fecha_vencimiento eliminada, rebuild OK.\n";
