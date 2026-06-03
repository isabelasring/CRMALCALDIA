<?php

/**
 * Arreglo nuclear Julian: rol limpio, sin filtros custom, notificación nueva sin leer.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Entities\Notification;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

if (!$role || !$user) {
    echo "Falta rol o usuario\n";
    exit(1);
}

$data = [
    'Case' => [
        'create' => 'no',
        'read' => 'all',
        'edit' => 'all',
        'delete' => 'no',
        'stream' => 'all',
    ],
    'Notification' => [
        'create' => 'no',
        'read' => 'all',
        'edit' => 'yes',
        'delete' => 'no',
    ],
    'Contact' => [
        'create' => 'no',
        'read' => 'team',
        'edit' => 'no',
        'delete' => 'no',
    ],
    'Email' => [
        'create' => 'no',
        'read' => 'team',
        'edit' => 'no',
        'delete' => 'no',
    ],
];

$role->set('data', $data);
$role->set('fieldData', null);
$role->set('assignmentPermission', 'all');
$role->set('exportPermission', 'no');
$role->set('massUpdatePermission', 'no');
$em->saveEntity($role, ['skipHooks' => true]);

$roles = $user->getLinkMultipleIdList('roles') ?? [];
if (!in_array($role->getId(), $roles, true)) {
    $roles[] = $role->getId();
}
$user->setLinkMultipleIdList('roles', $roles);
$user->set('isAdmin', false);
$user->set('type', 'regular');
$user->set('isActive', true);
$em->saveEntity($user);

$prefs = $em->getEntityById('Preferences', $user->getId());
if ($prefs) {
    $prefs->set('tabList', ['Case', 'Contact', 'Email']);
    $prefs->set('defaultTab', 'Case');
    $em->saveEntity($prefs);
}

// Password bcrypt
$user->set('password', password_hash('Julian2026!', PASSWORD_BCRYPT));
$em->saveEntity($user, ['silent' => true]);

// Nueva notificación SIN LEER
$case = $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->findOne();
if ($case) {
    foreach ($em->getRDBRepository('Notification')->where(['userId' => $user->getId()])->find() as $old) {
        $em->removeEntity($old);
    }

    $href = '#Case/view/' . $case->getId();
    $name = (string) $case->get(Field::NAME);
    $msg = 'Caso radicado listo para asignar patrullero: <a href="' . $href . '">' . htmlspecialchars($name) . '</a>';

    $n = $em->getRDBRepositoryByClass(Notification::class)->getNew();
    $n->setType('Info')
        ->setUserId($user->getId())
        ->setMessage($msg)
        ->setRead(false)
        ->setData([
            'entityType' => 'Case',
            'entityId' => $case->getId(),
            'entityName' => $name,
        ])
        ->setRelated(LinkParent::createFromEntity($case));
    $em->saveEntity($n);
    echo "Notificación nueva (sin leer) creada.\n";
}

echo "Rol Asignador limpio.\n";
echo "Usuario: julian.asignador\n";
echo "Password: Julian2026!\n";
echo "Casos Radicado: " . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . "\n";
echo "IMPORTANTE: rebuild + clear-cache después.\n";
