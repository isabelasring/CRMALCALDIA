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

echo 'Usuario: ' . $user->get('name') . PHP_EOL;
echo 'Roles: ' . implode(', ', $user->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;

$roles = $entityManager
    ->getRDBRepository('Role')
    ->find();

foreach ($roles as $role) {
    echo PHP_EOL . 'Rol: ' . $role->get('name') . ' (' . $role->getId() . ')' . PHP_EOL;
    $data = $role->get('data');
    if (is_object($data)) {
        $data = (array) $data;
    }
    if (!is_array($data)) {
        continue;
    }
    foreach (['Case', 'Email', 'InboundEmail', 'Contact'] as $scope) {
        if (!isset($data[$scope])) {
            continue;
        }
        $scopeData = (array) $data[$scope];
        echo "  {$scope}: ";
        echo 'read=' . ($scopeData['read'] ?? '-') . ' ';
        echo 'edit=' . ($scopeData['edit'] ?? '-') . ' ';
        echo 'create=' . ($scopeData['create'] ?? '-') . PHP_EOL;
    }
}
