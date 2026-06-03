<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$entityManager = $app->getContainer()->getByClass(EntityManager::class);

foreach ($entityManager->getRDBRepository('Case')->find() as $case) {
    echo $case->get('name') . ' | assigned: ' . $case->get('assignedUserId')
        . ' | teams: ' . implode(',', $case->getLinkMultipleIdList('teams') ?? [])
        . PHP_EOL;
}

$teams = $entityManager->getRDBRepository('Team')->find();
foreach ($teams as $team) {
    echo 'Team: ' . $team->get('name') . ' (' . $team->getId() . ')' . PHP_EOL;
}
