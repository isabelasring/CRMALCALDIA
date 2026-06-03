<?php

/**
 * Crea rol Asignador (Julian), equipo Patrulleros y permisos.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);

$roleName = 'Asignador';
$teamName = 'Patrulleros';

$role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

if (!$role) {
    $role = $em->getRDBRepository('Role')->getNew();
    $role->set('name', $roleName);
    $em->saveEntity($role);
    echo "Rol {$roleName} creado.\n";
}

$roleData = $role->get('data');
if ($roleData instanceof stdClass) {
    $roleData = json_decode(json_encode($roleData), true);
}
if (!is_array($roleData)) {
    $roleData = [];
}

foreach ([
    'Case' => ['create' => 'no', 'read' => 'all', 'edit' => 'all', 'delete' => 'no', 'stream' => 'all'],
    'Contact' => ['create' => 'yes', 'read' => 'all', 'edit' => 'all', 'delete' => 'no', 'stream' => 'team'],
    'User' => ['create' => 'no', 'read' => 'all', 'edit' => 'no', 'delete' => 'no'],
    'Email' => ['create' => 'yes', 'read' => 'all', 'edit' => 'all', 'delete' => 'no'],
    'Notification' => ['create' => 'no', 'read' => 'all', 'edit' => 'no', 'delete' => 'no'],
] as $scope => $permissions) {
    $roleData[$scope] = $permissions;
}

$role->set('data', $roleData);
$role->set('assignmentPermission', 'all');
$role->set('userPermission', 'all');

$fieldData = $role->get('fieldData');
if ($fieldData instanceof stdClass) {
    $fieldData = json_decode(json_encode($fieldData), true);
}
if (!is_array($fieldData)) {
    $fieldData = [];
}
if (!isset($fieldData['Case'])) {
    $fieldData['Case'] = [];
}
$caseFields = array_keys($metadata->get(['entityDefs', 'Case', 'fields']) ?? []);
$fieldData['Case'] = [];
foreach ($caseFields as $field) {
    if (in_array($field, ['assignedUser', 'assignedUserId'], true)) {
        $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'yes'];
    } else {
        $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'no'];
    }
}
$role->set('fieldData', $fieldData);
$em->saveEntity($role);

echo "Rol {$roleName}: Case leer/editar todo, asignar usuarios.\n";

$team = $em->getRDBRepository('Team')->where(['name' => $teamName])->findOne();

if (!$team) {
    $team = $em->getRDBRepository('Team')->getNew();
    $team->set('name', $teamName);
    $em->saveEntity($team);
    echo "Equipo {$teamName} creado.\n";
} else {
    echo "Equipo {$teamName} ya existe.\n";
}

echo PHP_EOL . 'Manual en CRM:' . PHP_EOL;
echo "1. Usuario Julian → rol Asignador, equipo propio (opcional).\n";
echo "2. Usuarios patrulleros → equipo Patrulleros (sin rol Asignador).\n";
echo "3. Caso Radicado → Julian elige Asignado a = patrullero.\n";
