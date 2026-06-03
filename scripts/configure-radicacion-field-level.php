<?php

/**
 * Solo el rol Radicación puede ver/editar cNumeroRadicacion.
 * Ejecutar una vez después del rebuild.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $entityManager */
$entityManager = $app->getContainer()->getByClass(EntityManager::class);

$field = 'cNumeroRadicacion';
$scope = 'Case';
$roleRadicacion = 'Radicación';

foreach ($entityManager->getRDBRepository('Role')->find() as $role) {
    try {
        $fieldData = $role->get('fieldData');

        if ($fieldData instanceof stdClass) {
            $fieldData = json_decode(json_encode($fieldData), true);
        }

        if (!is_array($fieldData)) {
            $fieldData = [];
        }

        if (!isset($fieldData[$scope])) {
            $fieldData[$scope] = [];
        }

        if ($fieldData[$scope] instanceof stdClass) {
            $fieldData[$scope] = json_decode(json_encode($fieldData[$scope]), true);
        }

        if (!is_array($fieldData[$scope])) {
            $fieldData[$scope] = [];
        }

        if ($role->get('name') === $roleRadicacion) {
            $fieldData[$scope][$field] = ['read' => 'yes', 'edit' => 'yes'];
            echo 'OK lectura/edición: ' . $role->get('name') . PHP_EOL;
        } else {
            $fieldData[$scope][$field] = ['read' => 'no', 'edit' => 'no'];
            echo 'Oculto para rol: ' . $role->get('name') . PHP_EOL;
        }

        $role->set('fieldData', $fieldData);
        $entityManager->saveEntity($role);
    } catch (Throwable $e) {
        echo 'Error en rol ' . $role->get('name') . ': ' . $e->getMessage() . PHP_EOL;
    }
}

echo "Listo. Campo {$field} solo editable por rol {$roleRadicacion}.\n";
