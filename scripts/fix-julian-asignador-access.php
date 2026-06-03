<?php

/**
 * Corrige acceso de Julian (rol Asignador):
 * - Ver casos Radicados (menú + lista)
 * - Editar solo assignedUser (permiso de asignación + field level)
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$roleName = 'Asignador';
$userName = 'julian.asignador';

$role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();
$user = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();

if (!$role || !$user) {
    echo "Falta rol Asignador o usuario julian.asignador.\n";
    exit(1);
}

$roleData = $role->get('data');
if ($roleData instanceof stdClass) {
    $roleData = json_decode(json_encode($roleData), true);
}
if (!is_array($roleData)) {
    $roleData = [];
}

$roleData['Case'] = [
    'create' => 'no',
    'read' => 'all',
    'edit' => 'all',
    'delete' => 'no',
    'stream' => 'all',
];
$roleData['Notification'] = [
    'create' => 'no',
    'read' => 'all',
    'edit' => 'no',
    'delete' => 'no',
];
$role->set('data', $roleData);

$role->set('assignmentPermission', 'all');
$role->set('userPermission', 'team');
$role->set('messagePermission', 'team');

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

$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);
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

echo "Rol Asignador: assignmentPermission=all, Case edit=all, solo assignedUser editable.\n";

$roles = $user->getLinkMultipleIdList('roles') ?? [];
if (!in_array($role->getId(), $roles, true)) {
    $roles[] = $role->getId();
    $user->setLinkMultipleIdList('roles', $roles);
}

$em->saveEntity($user);

$preferencesId = $user->getId();
$preferences = $em->getEntityById('Preferences', $preferencesId);

if ($preferences) {
    $tabList = $preferences->get('tabList');
    if (!is_array($tabList)) {
        $tabList = ['Case', 'Contact', 'Email'];
    }
    if (!in_array('Case', $tabList, true)) {
        array_unshift($tabList, 'Case');
    }
    $preferences->set('tabList', array_values(array_unique($tabList)));
    $preferences->set('defaultTab', 'Case');
    $em->saveEntity($preferences);
    echo "Preferencias: menú con Casos para Julian.\n";
}

echo PHP_EOL . 'Julian: cierra sesión y vuelve a entrar.' . PHP_EOL;
echo 'Casos Radicados en BD: '
    . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . PHP_EOL;
