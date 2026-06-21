<?php

/**
 * Permisos de la entidad ComunicacionCaso por rol.
 *
 * docker cp scripts/configure-comunicacion-caso-entity.php espocrm:/tmp/configure-comunicacion-caso-entity.php
 * docker exec espocrm php /tmp/configure-comunicacion-caso-entity.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);

if (!$metadata->get(['scopes', 'ComunicacionCaso', 'entity'])) {
    echo "Entidad ComunicacionCaso no encontrada. Ejecuta rebuild después de desplegar metadata.\n";
    exit(1);
}

$fields = array_keys($metadata->get(['entityDefs', 'ComunicacionCaso', 'fields']) ?? []);

$roleConfigs = [
    'Inspección' => [
        'scope' => ['create' => 'yes', 'read' => 'all', 'edit' => 'all', 'delete' => 'no', 'stream' => 'no'],
        'fieldEdit' => $fields,
        'fieldRead' => $fields,
    ],
    'Patrullero' => [
        'scope' => ['create' => 'yes', 'read' => 'all', 'edit' => 'own', 'delete' => 'no', 'stream' => 'no'],
        'fieldEdit' => $fields,
        'fieldRead' => $fields,
    ],
    'Asignador' => [
        'scope' => ['create' => 'no', 'read' => 'all', 'edit' => 'no', 'delete' => 'no', 'stream' => 'no'],
        'fieldEdit' => [],
        'fieldRead' => $fields,
    ],
    'Radicación' => [
        'scope' => ['create' => 'no', 'read' => 'all', 'edit' => 'no', 'delete' => 'no', 'stream' => 'no'],
        'fieldEdit' => [],
        'fieldRead' => $fields,
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

    $data['ComunicacionCaso'] = $cfg['scope'];
    $role->set('data', $data);

    $fieldData = $role->get('fieldData');

    if ($fieldData instanceof stdClass) {
        $fieldData = json_decode(json_encode($fieldData), true);
    }

    if (!is_array($fieldData)) {
        $fieldData = [];
    }

    $fieldData['ComunicacionCaso'] = [];

    foreach ($fields as $field) {
        $fieldData['ComunicacionCaso'][$field] = [
            'read' => in_array($field, $cfg['fieldRead'], true) ? 'yes' : 'no',
            'edit' => in_array($field, $cfg['fieldEdit'], true) ? 'yes' : 'no',
        ];
    }

    $role->set('fieldData', $fieldData);
    $em->saveEntity($role);

    echo "Rol {$roleName}: permisos ComunicacionCaso configurados.\n";
}

echo "Listo.\n";
