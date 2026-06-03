<?php

/**
 * Arreglo definitivo Julian: quitar fieldData roto, restaurar filtros, notificaciones estándar.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\PasswordHash;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

if (!$role || !$user) {
    echo "Falta rol o usuario.\n";
    exit(1);
}

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
$data['Contact'] = $data['Contact'] ?? [
    'create' => 'no',
    'read' => 'team',
    'edit' => 'no',
    'delete' => 'no',
];
$data['Email'] = $data['Email'] ?? [
    'create' => 'no',
    'read' => 'team',
    'edit' => 'no',
    'delete' => 'no',
];

$role->set('data', $data);
$role->set('assignmentPermission', 'all');
$role->set('fieldData', (object) []); // Sin field level: evita bloqueos de lista/API
$em->saveEntity($role, ['skipHooks' => true]);

echo "Rol: fieldData vacío, Case read/edit all.\n";

$roles = $user->getLinkMultipleIdList('roles') ?? [];
if (!in_array($role->getId(), $roles, true)) {
    $roles[] = $role->getId();
    $user->setLinkMultipleIdList('roles', $roles);
    $em->saveEntity($user);
}

$hash = $app->getContainer()->getByClass(PasswordHash::class)->hash('Julian2026!');
$user->set('password', $hash);
$em->saveEntity($user, ['silent' => true]);
echo "Password Julian: Julian2026!\n";

$prefs = $em->getEntityById('Preferences', $user->getId());
if ($prefs) {
    $prefs->set('tabList', ['Case', 'Contact', 'Email']);
    $prefs->set('defaultTab', 'Case');
    $em->saveEntity($prefs);
    echo "Preferencias menú OK.\n";
}

$fixed = 0;
foreach ($em->getRDBRepository('Notification')->where(['userId' => $user->getId()])->find() as $n) {
    if ($n->get('type') === 'Radicado') {
        $n->set('type', 'Info');
        $em->saveEntity($n);
        $fixed++;
    }
}
echo "Notificaciones Radicado -> Info: {$fixed}\n";

echo 'Casos Radicado en BD: '
    . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . PHP_EOL;
echo "Ejecuta: rebuild + clear-cache\n";
