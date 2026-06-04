<?php

/**
 * Permisos de campo: acta de visita solo editable por patrulleros (vía hook + UI).
 * Oculta lectura del acta para roles administrativos del flujo.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);

$cierreFields = [
    'cCierreFecha',
    'cCierreResumen',
    'cCierreConclusiones',
    'cCierreMedidasAdoptadas',
    'cCierreObservaciones',
    'cCierreEstado',
    'cCierreProcesoCompleto',
    'cCierreFechaRegistro',
];

$actaFields = [
    'cActaFechaVisita',
    'cActaHoraVisita',
    'cActaDireccionVisita',
    'cActaNombreVisitado',
    'cActaDocumentoVisitado',
    'cActaHallazgos',
    'cActaMedidasTomadas',
    'cActaObservaciones',
    'cActaEstado',
    'cActaVistoBueno',
    'cActaObservacionesRevision',
    'cActaFechaAprobacion',
    'cActaRegistroOficial',
];

$rolePatrulleroName = 'Patrullero';

$rolePatrullero = $em->getRDBRepository('Role')->where(['name' => $rolePatrulleroName])->findOne();

if (!$rolePatrullero) {
    $rolePatrullero = $em->getRDBRepository('Role')->getNew();
    $rolePatrullero->set('name', $rolePatrulleroName);
    $em->saveEntity($rolePatrullero);
    echo "Rol {$rolePatrulleroName} creado.\n";
}

$roleData = [
    'Case' => ['create' => 'no', 'read' => 'all', 'edit' => 'all', 'delete' => 'no', 'stream' => 'team'],
    'Notification' => ['create' => 'no', 'read' => 'all', 'edit' => 'no', 'delete' => 'no'],
];

$rolePatrullero->set('data', $roleData);
$rolePatrullero->set('assignmentPermission', 'no');
$rolePatrullero->set('userPermission', 'no');

$fieldDataPatrullero = ['Case' => []];
$caseFields = array_keys($metadata->get(['entityDefs', 'Case', 'fields']) ?? []);

foreach ($caseFields as $field) {
    if (in_array($field, $actaFields, true)) {
        $fieldDataPatrullero['Case'][$field] = ['read' => 'yes', 'edit' => 'yes'];
    } elseif (in_array($field, $cierreFields, true)) {
        $fieldDataPatrullero['Case'][$field] = ['read' => 'no', 'edit' => 'no'];
    } else {
        $fieldDataPatrullero['Case'][$field] = ['read' => 'yes', 'edit' => 'no'];
    }
}

$rolePatrullero->set('fieldData', $fieldDataPatrullero);
$em->saveEntity($rolePatrullero);

echo "Rol {$rolePatrulleroName}: leer casos, editar solo acta.\n";

$rolesHideActa = ['Asignador', 'Radicación'];

foreach ($rolesHideActa as $roleName) {
    $role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

    if (!$role) {
        echo "Rol no encontrado: {$roleName}\n";
        continue;
    }

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

    foreach (array_merge($actaFields, $cierreFields) as $field) {
        $fieldData['Case'][$field] = ['read' => 'no', 'edit' => 'no'];
    }

    $role->set('fieldData', $fieldData);
    $em->saveEntity($role);

    echo "Rol {$roleName}: campos acta ocultos.\n";
}

$tabs = ['Case', 'Notification'];

foreach (['patrullero.1', 'patrullero.2'] as $userName) {
    $user = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();

    if (!$user) {
        echo "Usuario no encontrado: {$userName}\n";
        continue;
    }

    $prefs = $em->getEntityById('Preferences', $user->getId());

    $roles = $user->getLinkMultipleIdList('roles') ?? [];

    if (!in_array($rolePatrullero->getId(), $roles, true)) {
        $roles[] = $rolePatrullero->getId();
        $user->setLinkMultipleIdList('roles', $roles);
        $em->saveEntity($user);
        echo "{$userName}: rol Patrullero asignado.\n";
    }

    if ($prefs) {
        $prefs->set('tabList', $tabs);
        $prefs->set('defaultTab', 'Case');
        $em->saveEntity($prefs);
        echo "{$userName}: menú Casos + Notificaciones.\n";
    }
}

echo "Listo. Ejecuta rebuild y clear-cache.\n";
