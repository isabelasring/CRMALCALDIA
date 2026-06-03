<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

foreach ($em->getRDBRepository('Role')->find() as $role) {
    $fieldData = $role->get('fieldData');
    if ($fieldData instanceof stdClass) {
        $fieldData = json_decode(json_encode($fieldData), true);
    }
    $access = $fieldData['Case']['cNumeroRadicacion'] ?? 'sin restricción (hereda)';
    echo $role->get('name') . ': ' . json_encode($access) . PHP_EOL;
}
