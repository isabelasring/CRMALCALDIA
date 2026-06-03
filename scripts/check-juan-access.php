<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$users = $em->getRDBRepository('User')
    ->where(['type' => 'regular', 'isActive' => true])
    ->find();

echo "=== Usuarios regulares ===" . PHP_EOL;

foreach ($users as $user) {
    echo PHP_EOL . 'userName: ' . $user->get('userName');
    echo ' | name: ' . $user->get('name');
    echo ' | roles: ' . implode(',', $user->getLinkMultipleIdList('roles') ?? []);
    echo PHP_EOL;
}

$role = $em->getRDBRepository('Role')->where(['name' => 'Inspección'])->findOne();

if (!$role) {
    echo PHP_EOL . 'Rol Inspección: NO EXISTE' . PHP_EOL;
    exit(1);
}

echo PHP_EOL . '=== Rol Inspección ===' . PHP_EOL;
echo 'id: ' . $role->getId() . PHP_EOL;

$data = $role->get('data');
if ($data instanceof stdClass) {
    $data = json_decode(json_encode($data), true);
}
echo 'Case: ' . json_encode($data['Case'] ?? 'no') . PHP_EOL;

$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);
$nav = $metadata->get(['app', 'client', 'menu']) ?? [];
$roleNav = $role->get('layoutSetId');
echo 'layoutSetId: ' . ($role->get('layoutSetId') ?: '(default)') . PHP_EOL;

$tabList = $role->get('tabList');
echo 'tabList role: ' . json_encode($tabList) . PHP_EOL;

echo PHP_EOL . 'Total casos: ' . $em->getRDBRepository('Case')->count() . PHP_EOL;
