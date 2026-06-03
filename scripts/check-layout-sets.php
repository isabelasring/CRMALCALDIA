<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

foreach ($em->getRDBRepository('LayoutSet')->find() as $set) {
    echo 'LayoutSet: ' . $set->get('name') . ' id=' . $set->getId() . PHP_EOL;
}

$role = $em->getRDBRepository('Role')->where(['name' => 'Radicación'])->findOne();
if ($role) {
    echo 'Role layoutSetId: ' . ($role->get('layoutSetId') ?: '(default)') . PHP_EOL;
}

$user = $em->getRDBRepository('User')->where(['userName' => 'edwin.radicacion'])->findOne();
if ($user) {
    echo 'User layoutSetId: ' . ($user->get('layoutSetId') ?: '(default)') . PHP_EOL;
}
