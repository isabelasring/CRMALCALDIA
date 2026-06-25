<?php

/**
 * Permisos de la entidad AsignacionHistorial por rol.
 *
 * docker cp scripts/configure-asignacion-historial.php espocrm:/tmp/configure-asignacion-historial.php
 * docker exec espocrm php /tmp/configure-asignacion-historial.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);

if (!$metadata->get(['scopes', 'AsignacionHistorial', 'entity'])) {
    echo "AVISO: entidad AsignacionHistorial no encontrada. Ejecuta rebuild después de desplegar metadata.\n";
    exit(0);
}

$historialFields = array_keys($metadata->get(['entityDefs', 'AsignacionHistorial', 'fields']) ?? []);

$roleConfigs = [
    'Asignador' => [
        'scope' => ['create' => 'no', 'read' => 'all', 'edit' => 'no', 'delete' => 'no', 'stream' => 'no'],
        'fieldRead' => $historialFields,
    ],
    'Inspección' => [
        'scope' => ['create' => 'no', 'read' => 'all', 'edit' => 'no', 'delete' => 'no', 'stream' => 'no'],
        'fieldRead' => $historialFields,
    ],
    'Radicación' => [
        'scope' => ['create' => 'no', 'read' => 'no', 'edit' => 'no', 'delete' => 'no', 'stream' => 'no'],
        'fieldRead' => [],
    ],
    'Patrullero' => [
        'scope' => ['create' => 'no', 'read' => 'no', 'edit' => 'no', 'delete' => 'no', 'stream' => 'no'],
        'fieldRead' => [],
    ],
];

foreach ($roleConfigs as $roleName => $cfg) {
    $role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

    if (!$role) {
        echo "Rol no encontrado: {$roleName}\n";
        continue;
    }

    $data = $role->get('data');

    if ($data instanceof stdClass) {
        $data = json_decode(json_encode($data), true);
    }

    if (!is_array($data)) {
        $data = [];
    }

    $data['AsignacionHistorial'] = $cfg['scope'];
    $role->set('data', $data);

    $fieldData = $role->get('fieldData');

    if ($fieldData instanceof stdClass) {
        $fieldData = json_decode(json_encode($fieldData), true);
    }

    if (!is_array($fieldData)) {
        $fieldData = [];
    }

    $fieldData['AsignacionHistorial'] = [];

    foreach ($historialFields as $field) {
        $fieldData['AsignacionHistorial'][$field] = [
            'read' => in_array($field, $cfg['fieldRead'], true) ? 'yes' : 'no',
            'edit' => 'no',
        ];
    }

    $role->set('fieldData', $fieldData);
    $em->saveEntity($role);

    echo "Rol {$roleName}: permisos AsignacionHistorial configurados.\n";
}

$asignadorRole = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();

if ($asignadorRole) {
    $fieldData = $asignadorRole->get('fieldData');

    if ($fieldData instanceof stdClass) {
        $fieldData = json_decode(json_encode($fieldData), true);
    }

    if (!is_array($fieldData)) {
        $fieldData = [];
    }

    if (!isset($fieldData['Case']) || !is_array($fieldData['Case'])) {
        $fieldData['Case'] = [];
    }

    $fieldData['Case']['cMotivoReasignacion'] = [
        'read' => 'yes',
        'edit' => 'yes',
    ];

    $asignadorRole->set('fieldData', $fieldData);

    $data = $asignadorRole->get('data');

    if ($data instanceof stdClass) {
        $data = json_decode(json_encode($data), true);
    }

    if (!is_array($data)) {
        $data = [];
    }

    if (!isset($data['Case']) || !is_array($data['Case'])) {
        $data['Case'] = [];
    }

    $data['Case']['create'] = 'no';
    $data['Case']['read'] = $data['Case']['read'] ?? 'all';
    $data['Case']['edit'] = $data['Case']['edit'] ?? 'all';
    $data['Case']['delete'] = 'no';
    $data['Case']['stream'] = $data['Case']['stream'] ?? 'all';

    $asignadorRole->set('data', $data);
    $em->saveEntity($asignadorRole);

    echo "Rol Asignador: cMotivoReasignacion editable; Case create=no.\n";
}

echo "Listo.\n";
