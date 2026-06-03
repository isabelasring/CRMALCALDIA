<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
$data = $role->get('data');
if ($data instanceof stdClass) {
    $data = json_decode(json_encode($data), true);
}
if (!is_array($data)) {
    $data = [];
}

// Asegurar todos los scopes visibles que Julian necesita
foreach (['Case', 'Notification', 'Contact', 'Email', 'Stream'] as $scope) {
    if ($scope === 'Case') {
        $data[$scope] = [
            'create' => 'no',
            'read' => 'all',
            'edit' => 'all',
            'delete' => 'no',
            'stream' => 'all',
        ];
    } elseif ($scope === 'Notification') {
        $data[$scope] = [
            'create' => 'no',
            'read' => 'all',
            'edit' => 'yes',
            'delete' => 'no',
        ];
    } else {
        $data[$scope] = $data[$scope] ?? [
            'create' => 'no',
            'read' => 'team',
            'edit' => 'no',
            'delete' => 'no',
        ];
    }
}

$role->set('data', $data);
$em->saveEntity($role, ['skipHooks' => true]);
echo "Scopes actualizados en rol Asignador.\n";

// Verificar mapa ACL
$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
$mapData = $app->getContainer()->get('aclManager')->getMapData($user);
echo 'Case en ACL map: ' . (isset($mapData->Case) ? json_encode($mapData->Case) : 'NO') . PHP_EOL;
echo 'tables: ' . (isset($mapData->scopes) ? 'has scopes' : 'no scopes key') . PHP_EOL;
if (isset($mapData->scopes)) {
    echo 'Case scope visible: ' . (in_array('Case', $mapData->scopes ?? [], true) ? 'yes' : json_encode($mapData->scopes)) . PHP_EOL;
}
