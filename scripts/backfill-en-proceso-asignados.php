<?php

/**
 * Casos Radicado ya asignados a patrullero → pasan a En proceso.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$team = $em->getRDBRepositoryByClass(Team::class)
    ->where(['name' => 'Patrulleros'])
    ->findOne();

if (!$team) {
    echo "Equipo Patrulleros no encontrado.\n";
    exit(1);
}

$updated = 0;

foreach ($em->getRDBRepository('Case')->where(['status' => 'Radicado'])->find() as $case) {
    $assignedUserId = $case->get('assignedUserId');

    if (!$assignedUserId) {
        continue;
    }

    $assignedUser = $em->getEntityById(User::ENTITY_TYPE, $assignedUserId);

    if (!$assignedUser) {
        continue;
    }

    $teams = $assignedUser->getLinkMultipleIdList('teams') ?? [];

    if (!in_array($team->getId(), $teams, true)) {
        continue;
    }

    $case->set('status', 'En proceso');
    $em->saveEntity($case);
    $updated++;
    echo 'Actualizado: ' . $case->get('name') . "\n";
}

echo "Total: {$updated} caso(s) → En proceso.\n";
