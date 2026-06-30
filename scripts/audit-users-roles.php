<?php

/**
 * Audita usuarios activos: roles asignados y permiso de lectura en Case.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$hasWarnings = false;

$users = $em->getRDBRepository('User')
    ->where([
        'isActive' => true,
        'deleted' => false,
        'type' => ['regular', 'admin'],
    ])
    ->find();

foreach ($users as $user) {
    $userName = (string) $user->get('userName');
    $roleIds = $user->getLinkMultipleIdList('roles') ?? [];
    $roleNames = [];

    foreach ($roleIds as $roleId) {
        $role = $em->getEntityById('Role', $roleId);

        if ($role) {
            $roleNames[] = (string) $role->get('name');
        }
    }

    $rolesLabel = $roleNames === [] ? '(sin roles)' : implode(', ', $roleNames);

    echo "{$userName}: roles={$rolesLabel}\n";

    if ($user->isAdmin()) {
        continue;
    }

    foreach ($roleNames as $roleName) {
        $role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

        if (!$role) {
            continue;
        }

        $data = $role->get('data');

        if ($data instanceof stdClass) {
            $data = json_decode(json_encode($data), true);
        }

        $caseRead = is_array($data) ? ($data['Case']['read'] ?? 'no') : 'no';

        if ($caseRead !== 'all') {
            echo "  AVISO: rol {$roleName} sin lectura Case=all (actual: {$caseRead}).\n";
            $hasWarnings = true;
        }
    }
}

if ($hasWarnings) {
    echo "\nAVISO: hay usuarios con permisos incompletos. Ejecute configure-alcaldia-no-roles-mode.php.\n";
}

echo "\nAuditoría de usuarios finalizada.\n";
