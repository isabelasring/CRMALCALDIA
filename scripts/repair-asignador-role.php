<?php

/**
 * Repara rol Asignador: fieldData solo con campos que existen en Case.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Metadata;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);
$metadata = $app->getContainer()->getByClass(Metadata::class);

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();

if (!$role) {
    echo "Rol Asignador no existe.\n";
    exit(1);
}

$caseFields = array_keys($metadata->get(['entityDefs', 'Case', 'fields']) ?? []);

$allowed = array_merge($caseFields, ['assignedUser', 'assignedUserId']);

$fieldData = ['Case' => []];

foreach ($allowed as $field) {
    if (in_array($field, ['assignedUser', 'assignedUserId'], true)) {
        $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'yes'];
    } else {
        $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'no'];
    }
}

$role->set('assignmentPermission', 'all');
$role->set('fieldData', $fieldData);

$data = $role->get('data');
if ($data instanceof stdClass) {
    $data = json_decode(json_encode($data), true);
}
if (!is_array($data)) {
    $data = [];
}
$data['Case'] = [
    'create' => 'no',
    'read' => 'all',
    'edit' => 'all',
    'delete' => 'no',
    'stream' => 'all',
];
$data['Notification'] = [
    'create' => 'no',
    'read' => 'all',
    'edit' => 'no',
    'delete' => 'no',
];
$role->set('data', $data);

$em->saveEntity($role, ['skipHooks' => true]);

echo 'Rol Asignador reparado. Campos Case en fieldData: ' . count($fieldData['Case']) . PHP_EOL;
