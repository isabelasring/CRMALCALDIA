<?php

/**
 * Julian (Asignador): permisos, notificaciones no leídas, patrulleros en equipo.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Entities\Notification;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);
$metadata = $app->getContainer()->get('metadata');

$roleName = 'Asignador';
$userName = 'julian.asignador';

$role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();
$julian = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();

if (!$role || !$julian) {
    echo "Falta rol Asignador o julian.asignador\n";
    exit(1);
}

// --- Rol: solo assignedUser editable + asignar usuarios ---
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
$roleData['User'] = [
    'create' => 'no',
    'read' => 'all',
    'edit' => 'no',
    'delete' => 'no',
];
$role->set('data', $roleData);
$role->set('assignmentPermission', 'all');
$role->set('userPermission', 'all');

$caseFields = array_keys($metadata->get(['entityDefs', 'Case', 'fields']) ?? []);
$fieldData = ['Case' => []];
foreach ($caseFields as $field) {
    if (in_array($field, ['assignedUser', 'assignedUserId'], true)) {
        $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'yes'];
    } else {
        $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'no'];
    }
}
$role->set('fieldData', $fieldData);
$em->saveEntity($role);
echo "Rol Asignador: assignmentPermission=all, userPermission=all, User read=all, solo assignedUser editable.\n";

$roles = $julian->getLinkMultipleIdList('roles') ?? [];
if (!in_array($role->getId(), $roles, true)) {
    $roles[] = $role->getId();
    $julian->setLinkMultipleIdList('roles', $roles);
    $em->saveEntity($julian);
}

$prefs = $em->getEntityById('Preferences', $julian->getId());
if ($prefs) {
    $prefs->set('tabList', ['Case', 'Contact', 'Email', 'Notification']);
    $prefs->set('defaultTab', 'Case');
    $em->saveEntity($prefs);
}

// --- Notificaciones no leídas para casos Radicado ---
$roleId = $role->getId();
$created = 0;
$unread = 0;

foreach ($em->getRDBRepository('Case')->where(['status' => 'Radicado'])->find() as $case) {
    $expediente = trim((string) ($case->get('cExpediente') ?? ''));
    if ($expediente === '') {
        echo "Caso sin expediente (se omite notif): " . $case->get('name') . "\n";
        continue;
    }

    $exists = false;
    foreach ($em->getRDBRepository('Notification')->where([
        'userId' => $julian->getId(),
        'relatedId' => $case->getId(),
        'relatedType' => 'Case',
    ])->find() as $n) {
        $data = $n->get('data');
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }
        if (is_array($data) && !empty($data['isAsignador'])) {
            $exists = true;
            if ($n->get('read')) {
                $n->set('read', false);
                $em->saveEntity($n);
                $unread++;
            }
            break;
        }
    }

    if ($exists) {
        continue;
    }

    $numero = trim((string) ($case->get('cNumeroRadicacion') ?? ''));
    $numeroLabel = $numero !== '' ? $numero : 'sin número';
    $entityName = (string) $case->get(Field::NAME);
    $caseHref = '#Case/view/' . $case->getId();
    $messagePlain = 'Caso para asignar: '
        . htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8')
        . ' · Expediente '
        . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
        . ' (N.º ' . htmlspecialchars($numeroLabel, ENT_QUOTES, 'UTF-8') . ')';

    $n = $em->getRDBRepositoryByClass(Notification::class)->getNew();
    $n->setType(Notification::TYPE_MESSAGE);
    $n->setUserId($julian->getId());
    $n->setMessage($messagePlain);
    $n->set('read', false);
    $n->setData([
        'entityType' => 'Case',
        'entityId' => $case->getId(),
        'entityName' => $entityName,
        'numeroRadicacion' => $numeroLabel,
        'expediente' => $expediente,
        'isRadicado' => true,
        'isAsignador' => true,
        'recordUrl' => $caseHref,
    ]);
    $n->setRelated(LinkParent::createFromEntity($case));
    $em->saveEntity($n);
    $created++;
}

echo "Notificaciones nuevas: $created | marcadas no leídas: $unread\n";
echo 'Casos Radicado: ' . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . "\n";
echo "Julian: cierra sesión y vuelve a entrar.\n";
