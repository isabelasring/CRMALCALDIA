<?php

/**
 * Permisos amplios en Case para TODOS los roles (modo sin flujos por rol).
 * Restablece scope y field_data de Case a lectura/edición completa.
 *
 * docker cp scripts/reset-case-full-access-all-roles.php espocrm:/tmp/
 * docker exec espocrm php /tmp/reset-case-full-access-all-roles.php
 * docker exec espocrm php command.php clear-cache
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Metadata;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
/** @var Metadata $metadata */
$metadata = $app->getContainer()->getByClass(Metadata::class);

$scope = 'Case';
$caseFieldNames = array_keys($metadata->get(['entityDefs', 'Case', 'fields']) ?? []);
$fullScope = [
    'create' => 'yes',
    'read' => 'all',
    'edit' => 'all',
    'delete' => 'yes',
    'stream' => 'all',
];

foreach ($em->getRDBRepository('Role')->find() as $role) {
    $name = (string) $role->get('name');

    if ($name === '') {
        continue;
    }

    $data = $role->get('data');

    if ($data instanceof stdClass) {
        $data = json_decode(json_encode($data), true);
    }

    if (!is_array($data)) {
        $data = [];
    }

    $data[$scope] = $fullScope;

    $fieldData = $role->get('fieldData');

    if ($fieldData instanceof stdClass) {
        $fieldData = json_decode(json_encode($fieldData), true);
    }

    if (!is_array($fieldData)) {
        $fieldData = [];
    }

    if (!isset($fieldData[$scope]) || !is_array($fieldData[$scope])) {
        $fieldData[$scope] = [];
    }

    foreach ($caseFieldNames as $field) {
        $fieldData[$scope][$field] = ['read' => 'yes', 'edit' => 'yes'];
    }

    $role->set('data', $data);
    $role->set('fieldData', $fieldData);
    $role->set('assignmentPermission', 'all');

    $em->saveEntity($role);

    echo "Rol {$name}: Case con acceso completo (scope + campos).\n";
}

if (getenv('ESPO_DEPLOY_BATCH') !== '1') {
    chdir('/var/www/html');
    passthru('php command.php rebuild');
    passthru('php command.php clear-cache');
}

echo "Listo. Cierra sesión y vuelve a entrar.\n";
