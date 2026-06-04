<?php

/**
 * Permisos de campo cTipo (Juan) y assignedUser (Julian) tras radicado + expediente.
 *
 * docker cp scripts/configure-post-radicacion-fields.php espocrm:/tmp/
 * docker exec espocrm php /tmp/configure-post-radicacion-fields.php
 * docker exec espocrm php command.php clear-cache
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$pdo = $em->getPDO();

$scope = 'Case';
$roleInspeccion = 'Inspección';
$roleAsignador = 'Asignador';

$stmt = $pdo->query('SELECT id, name, field_data FROM role WHERE deleted = false');

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roleName = $row['name'];
    $fieldData = json_decode($row['field_data'] ?? '{}', true);

    if (!is_array($fieldData)) {
        $fieldData = [];
    }

    if (!isset($fieldData[$scope]) || !is_array($fieldData[$scope])) {
        $fieldData[$scope] = [];
    }

    if ($roleName === $roleInspeccion) {
        $fieldData[$scope]['cTipo'] = ['read' => 'yes', 'edit' => 'yes'];
        $fieldData[$scope]['cCategoria'] = ['read' => 'yes', 'edit' => 'yes'];
        $fieldData[$scope]['assignedUser'] = ['read' => 'yes', 'edit' => 'no'];
        $fieldData[$scope]['assignedUserId'] = ['read' => 'yes', 'edit' => 'no'];
        echo "Inspección: cTipo/cCategoria editable siempre, Asignado a solo lectura\n";
    } elseif ($roleName === $roleAsignador) {
        $fieldData[$scope]['cTipo'] = ['read' => 'yes', 'edit' => 'no'];
        $fieldData[$scope]['cCategoria'] = ['read' => 'yes', 'edit' => 'no'];
        $fieldData[$scope]['assignedUser'] = ['read' => 'yes', 'edit' => 'yes'];
        $fieldData[$scope]['assignedUserId'] = ['read' => 'yes', 'edit' => 'yes'];
        echo "Asignador: Asignado a editable, cTipo solo lectura\n";
    } else {
        $fieldData[$scope]['cTipo'] = ['read' => 'yes', 'edit' => 'no'];
        $fieldData[$scope]['cCategoria'] = ['read' => 'yes', 'edit' => 'no'];
        $fieldData[$scope]['assignedUser'] = ['read' => 'yes', 'edit' => 'no'];
        $fieldData[$scope]['assignedUserId'] = ['read' => 'yes', 'edit' => 'no'];
    }

    $update = $pdo->prepare(
        'UPDATE role SET field_data = :fieldData, modified_at = :now WHERE id = :id'
    );
    $update->execute([
        'fieldData' => json_encode($fieldData, JSON_UNESCAPED_UNICODE),
        'now' => date('Y-m-d H:i:s'),
        'id' => $row['id'],
    ]);
}

$app->getContainer()->getByClass(DataManager::class)->rebuild();
echo "Listo. Campos post-radicación configurados.\n";
