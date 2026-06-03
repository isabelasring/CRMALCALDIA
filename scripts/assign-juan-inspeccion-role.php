<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$userName = 'juan.inspeccion';
$roleName = 'Inspección';

$user = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();
$role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

if (!$user) {
    echo "Usuario {$userName} no encontrado.\n";
    exit(1);
}

if (!$role) {
    echo "Rol {$roleName} no encontrado. Ejecuta configure-inspeccion-access.php\n";
    exit(1);
}

$roles = $user->getLinkMultipleIdList('roles') ?? [];
if (!in_array($role->getId(), $roles, true)) {
    $roles[] = $role->getId();
    $user->setLinkMultipleIdList('roles', $roles);
}

$team = $em->getRDBRepository('Team')->where(['name' => 'Radicación'])->findOne();
if ($team) {
    $teams = $user->getLinkMultipleIdList('teams') ?? [];
    if (!in_array($team->getId(), $teams, true)) {
        $teams[] = $team->getId();
        $user->setLinkMultipleIdList('teams', $teams);
        $user->set('defaultTeamId', $team->getId());
    }
}

$em->saveEntity($user);

$tabList = $role->get('tabList');
if (!is_array($tabList) || !in_array('Case', $tabList, true)) {
    $role->set('tabList', [
        'Case',
        'Contact',
        'Account',
        'Document',
        'Email',
    ]);
    $em->saveEntity($role);
    echo "Menú del rol actualizado (Casos visible).\n";
}

echo "Listo: {$userName} tiene rol {$roleName}.\n";
echo "Cierra sesión de Juan y vuelve a entrar.\n";
