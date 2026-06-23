<?php

/**
 * Asigna roles operativos a usuarios conocidos (despliegue desde cero en Dokploy).
 * Idempotente: no duplica roles ya asignados.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$assignments = [
    'juan' => 'Inspección',
    'juan.inspeccion' => 'Inspección',
    'edwin' => 'Radicación',
    'edwin.radicacion' => 'Radicación',
    'julian' => 'Asignador',
    'julian.asignador' => 'Asignador',
];

foreach ($assignments as $userName => $roleName) {
    $user = $em->getRDBRepository('User')
        ->where(['userName' => $userName, 'isActive' => true])
        ->findOne();

    if (!$user) {
        echo "Usuario no encontrado (omitido): {$userName}\n";
        continue;
    }

    $role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

    if (!$role) {
        echo "Rol no encontrado: {$roleName}\n";
        continue;
    }

    $roleIds = $user->getLinkMultipleIdList('roles') ?? [];

    if (in_array($role->getId(), $roleIds, true)) {
        echo "Ya tiene rol {$roleName}: {$userName}\n";
        continue;
    }

    $roleIds[] = $role->getId();
    $user->setLinkMultipleIdList('roles', $roleIds);

    $team = $em->getRDBRepository('Team')->where(['name' => $roleName])->findOne();

    if ($team) {
        $teamIds = $user->getLinkMultipleIdList('teams') ?? [];

        if (!in_array($team->getId(), $teamIds, true)) {
            $teamIds[] = $team->getId();
            $user->setLinkMultipleIdList('teams', $teamIds);
        }

        if (!$user->get('defaultTeamId')) {
            $user->set('defaultTeamId', $team->getId());
        }
    }

    $em->saveEntity($user);
    echo "Rol {$roleName} asignado a {$userName}\n";
}

echo "Listo.\n";
