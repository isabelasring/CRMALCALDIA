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

echo 'name: ' . $user->get('name') . PHP_EOL;
echo 'type: ' . $user->get('type') . PHP_EOL;
echo 'isActive: ' . ($user->get('isActive') ? 'yes' : 'no') . PHP_EOL;
echo 'roles: ' . implode(', ', $user->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;
echo 'teams: ' . implode(', ', $user->getLinkMultipleIdList('teams') ?? []) . PHP_EOL;
echo 'defaultTeamId: ' . ($user->get('defaultTeamId') ?: '(none)') . PHP_EOL;
echo 'portalId: ' . ($user->get('portalId') ?: '(none)') . PHP_EOL;

$role = $entityManager
    ->getRDBRepository('Role')
    ->where(['name' => 'Radicación'])
    ->findOne();

if ($role) {
    echo PHP_EOL . 'Rol Radicación id: ' . $role->getId() . PHP_EOL;
    $data = $role->get('data');
    if (is_object($data)) {
        $data = (array) $data;
    }
    foreach (['Case', 'Email', 'Contact', 'Stream', 'Account', 'User'] as $scope) {
        if (!isset($data[$scope])) {
            echo "  {$scope}: (no definido en rol)\n";
            continue;
        }
        echo '  ' . $scope . ': ' . json_encode($data[$scope]) . PHP_EOL;
    }
}

$caseCount = $entityManager->getRDBRepository('Case')->count();
echo PHP_EOL . 'Total casos en BD: ' . $caseCount . PHP_EOL;
