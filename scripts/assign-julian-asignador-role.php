<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$userName = 'julian.asignador';
$roleName = 'Asignador';

$user = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();
$role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

if (!$user) {
    echo "Usuario {$userName} no encontrado.\n";
    exit(1);
}

if (!$role) {
    echo "Rol {$roleName} no encontrado. Ejecuta configure-asignador-patrulleros.php\n";
    exit(1);
}

$roles = $user->getLinkMultipleIdList('roles') ?? [];
if (!in_array($role->getId(), $roles, true)) {
    $roles[] = $role->getId();
    $user->setLinkMultipleIdList('roles', $roles);
}

// Julian NO debe estar en Patrulleros; solo los patrulleros van en ese equipo.
$patrulleros = $em->getRDBRepository('Team')->where(['name' => 'Patrulleros'])->findOne();
if ($patrulleros) {
    $teams = $user->getLinkMultipleIdList('teams') ?? [];
    $teams = array_values(array_filter(
        $teams,
        fn (string $id) => $id !== $patrulleros->getId()
    ));
    $user->setLinkMultipleIdList('teams', $teams);
}

$em->saveEntity($user);

$tabList = $role->get('tabList');
if (!is_array($tabList) || !in_array('Case', $tabList, true)) {
    $role->set('tabList', ['Case', 'Contact', 'Email']);
    $em->saveEntity($role);
    echo "Menú del rol actualizado (Casos visible).\n";
}

echo "Listo: {$userName} tiene rol {$roleName}.\n";
echo "Cierra sesión de Julian y vuelve a entrar.\n";
