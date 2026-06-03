<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $entityManager */
$entityManager = $app->getContainer()->getByClass(EntityManager::class);

$user = $entityManager
    ->getRDBRepository('User')
    ->where(['userName' => 'edwin.radicacion'])
    ->findOne();

if (!$user) {
    echo "Usuario no encontrado\n";
    exit(1);
}

$preferences = $entityManager->getEntityById('Preferences', $user->getId());

echo 'receiveAssignmentEmailNotifications: '
    . ($preferences?->get('receiveAssignmentEmailNotifications') ? 'yes' : 'no')
    . PHP_EOL;

echo 'assignmentEmailNotificationsIgnoreEntityTypeList: '
    . json_encode($preferences?->get('assignmentEmailNotificationsIgnoreEntityTypeList') ?? [])
    . PHP_EOL;

$cases = $entityManager->getRDBRepository('Case')->limit(0, 1)->find();

foreach ($cases as $case) {
    echo 'Entity type Case: ' . $case->getEntityType() . PHP_EOL;
    break;
}
