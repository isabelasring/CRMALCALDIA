<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$entityManager = $app->getContainer()->getByClass(EntityManager::class);

$case = $entityManager->getRDBRepository('Case')->findOne();

if ($case) {
    echo 'getEntityType: ' . $case->getEntityType() . PHP_EOL;
}
