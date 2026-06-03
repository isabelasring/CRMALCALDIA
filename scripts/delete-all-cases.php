<?php

/**
 * Elimina todos los casos (Case). No crea datos nuevos.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$cases = $em->getRDBRepository('Case')->find();
$count = 0;

foreach ($cases as $case) {
    echo 'Eliminando: ' . $case->get('name') . ' (' . $case->get('status') . ")\n";
    $em->removeEntity($case);
    $count++;
}

echo "\nTotal eliminados: $count\n";
echo 'Casos restantes: ' . $em->getRDBRepository('Case')->count() . "\n";
