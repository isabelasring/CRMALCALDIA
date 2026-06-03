<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

echo "=== Usuarios (buscar julian) ===" . PHP_EOL;

foreach ($em->getRDBRepository('User')->find() as $user) {
    $uname = strtolower((string) $user->get('userName'));
    $name = strtolower((string) $user->get('name'));

    if (!str_contains($uname, 'julian') && !str_contains($name, 'julian')) {
        continue;
    }

    echo 'userName: ' . $user->get('userName') . PHP_EOL;
    echo 'name: ' . $user->get('name') . PHP_EOL;
    echo 'isActive: ' . ($user->get('isActive') ? 'yes' : 'no') . PHP_EOL;
    echo 'type: ' . $user->get('type') . PHP_EOL;
    echo 'roles: ' . implode(',', $user->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;
    echo 'teams: ' . implode(',', $user->getLinkMultipleIdList('teams') ?? []) . PHP_EOL;
    echo 'email: ' . ($user->get('emailAddress') ?: '(vacío)') . PHP_EOL;
    echo '---' . PHP_EOL;
}

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();

if ($role) {
    echo PHP_EOL . 'Rol Asignador id: ' . $role->getId() . PHP_EOL;
    $data = $role->get('data');
    if ($data instanceof stdClass) {
        $data = json_decode(json_encode($data), true);
    }
    echo 'Case: ' . json_encode($data['Case'] ?? '-') . PHP_EOL;
    echo 'tabList: ' . json_encode($role->get('tabList')) . PHP_EOL;
}

echo PHP_EOL . 'Total casos Radicado: ' . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . PHP_EOL;
