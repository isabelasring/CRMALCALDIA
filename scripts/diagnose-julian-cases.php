<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

if (!$julian) {
    echo "julian.asignador no existe\n";
    exit(1);
}

echo 'Roles: ' . implode(',', $julian->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;

echo PHP_EOL . '=== Casos en BD ===' . PHP_EOL;
foreach ($em->getRDBRepository('Case')->find() as $case) {
    echo '- ' . $case->get('name') . ' | status=[' . $case->get('status') . ']' . PHP_EOL;
}

echo PHP_EOL . 'Radicado count: ' . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . PHP_EOL;

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
if ($role) {
    $data = $role->get('data');
    if ($data instanceof stdClass) {
        $data = json_decode(json_encode($data), true);
    }
    echo 'Case perms: ' . json_encode($data['Case'] ?? null) . PHP_EOL;
    $fd = $role->get('fieldData');
    if ($fd instanceof stdClass) {
        $fd = json_decode(json_encode($fd), true);
    }
    echo 'fieldData Case keys: ' . implode(',', array_keys($fd['Case'] ?? [])) . PHP_EOL;
    echo 'assignedUser field: ' . json_encode($fd['Case']['assignedUser'] ?? 'NOT SET') . PHP_EOL;
    echo 'tabList: ' . json_encode($role->get('tabList')) . PHP_EOL;
}

echo PHP_EOL . 'Mandatory class: '
    . (class_exists(\Espo\Custom\Classes\Select\Case\AccessControlFilters\Mandatory::class) ? 'OK' : 'MISSING')
    . PHP_EOL;
