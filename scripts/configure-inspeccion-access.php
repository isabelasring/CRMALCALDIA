<?php

/**
 * Configura rol Inspección (Juan): ver/crear/editar todos los casos.
 * Ejecutar después de crear el rol "Inspección" en el CRM.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $entityManager */
$entityManager = $app->getContainer()->getByClass(EntityManager::class);

$roleName = 'Inspección';

$role = $entityManager
    ->getRDBRepository('Role')
    ->where(['name' => $roleName])
    ->findOne();

if (!$role) {
    $role = $entityManager->getRDBRepository('Role')->getNew();
    $role->set('name', $roleName);
    $entityManager->saveEntity($role);
    echo "Rol '{$roleName}' creado.\n";
}

$roleData = $role->get('data');
if ($roleData instanceof stdClass) {
    $roleData = json_decode(json_encode($roleData), true);
}
if (!is_array($roleData)) {
    $roleData = [];
}

$scopes = [
    'Case' => [
        'create' => 'yes',
        'read' => 'all',
        'edit' => 'all',
        'delete' => 'no',
        'stream' => 'all',
    ],
    'Contact' => [
        'create' => 'yes',
        'read' => 'all',
        'edit' => 'all',
        'delete' => 'no',
        'stream' => 'team',
    ],
    'Account' => [
        'create' => 'yes',
        'read' => 'all',
        'edit' => 'all',
        'delete' => 'no',
        'stream' => 'team',
    ],
    'Email' => [
        'create' => 'yes',
        'read' => 'all',
        'edit' => 'all',
        'delete' => 'no',
    ],
    'Notification' => [
        'create' => 'no',
        'read' => 'all',
        'edit' => 'no',
        'delete' => 'no',
    ],
    'User' => [
        'create' => 'no',
        'read' => 'all',
        'edit' => 'no',
        'delete' => 'no',
    ],
];

foreach ($scopes as $scope => $permissions) {
    $roleData[$scope] = $permissions;
}

$role->set('data', $roleData);
$role->set('userPermission', 'all');

$fieldData = $role->get('fieldData');
if ($fieldData instanceof stdClass) {
    $fieldData = json_decode(json_encode($fieldData), true);
}
if (!is_array($fieldData)) {
    $fieldData = [];
}
if (!isset($fieldData['Case']) || !is_array($fieldData['Case'])) {
    $fieldData['Case'] = [];
}
$fieldData['Case']['cNumeroRadicado'] = ['read' => 'yes', 'edit' => 'no'];
$fieldData['Case']['cExpediente'] = ['read' => 'yes', 'edit' => 'no'];
$fieldData['Case']['cTipo'] = ['read' => 'yes', 'edit' => 'yes'];
$fieldData['Case']['cCategoria'] = ['read' => 'yes', 'edit' => 'yes'];
$fieldData['Case']['assignedUser'] = ['read' => 'yes', 'edit' => 'no'];
$fieldData['Case']['assignedUserId'] = ['read' => 'yes', 'edit' => 'no'];
unset($fieldData['Case']['cNumeroRadicacion']);
$fieldData['Case']['status'] = ['read' => 'yes', 'edit' => 'yes'];

$role->set('fieldData', $fieldData);
$entityManager->saveEntity($role);

echo "Rol {$roleName} configurado: Case crear/leer/editar = all\n";
echo "Inspección: lectura de cNumeroRadicado/cExpediente (sin edición).\n";

$users = $entityManager
    ->getRDBRepository('User')
    ->where(['isActive' => true, 'type' => 'regular'])
    ->find();

echo PHP_EOL . 'Usuarios con rol Inspección:' . PHP_EOL;

$found = 0;
foreach ($users as $user) {
    $roles = $user->getLinkMultipleIdList('roles') ?? [];
    if (!in_array($role->getId(), $roles, true)) {
        continue;
    }
    $found++;
    echo '- ' . $user->get('userName') . ' | email: ' . ($user->get('emailAddress') ?: '(vacío)') . PHP_EOL;
}

if ($found === 0) {
    echo "(ninguno) → Crea Juan y asígnale el rol Inspección.\n";
}

$config = $app->getContainer()->getByClass(Espo\Core\Utils\Config::class);
echo PHP_EOL . 'SMTP sistema: ' . ($config->get('smtpServer') ?: '(no configurado)') . PHP_EOL;
