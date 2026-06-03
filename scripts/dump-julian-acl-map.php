<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

$mapData = $app->getContainer()->get('aclManager')->getMapData($user);
echo json_encode($mapData, JSON_PRETTY_PRINT) . PHP_EOL;
