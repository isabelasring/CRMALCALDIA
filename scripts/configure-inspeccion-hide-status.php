<?php

/**
 * Oculta el campo status para el rol Inspección (Juan no lo elige al crear casos).
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$role = $em->getRDBRepository('Role')->where(['name' => 'Inspección'])->findOne();

if (!$role) {
    echo "Rol Inspección no encontrado. Ejecuta configure-inspeccion-access.php\n";
    exit(1);
}

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

$fieldData['Case']['status'] = ['read' => 'no', 'edit' => 'no'];
$fieldData['Case']['cNumeroRadicacion'] = ['read' => 'no', 'edit' => 'no'];

$role->set('fieldData', $fieldData);
$em->saveEntity($role);

echo "Rol Inspección: campo status oculto (read/edit = no).\n";
echo "Al crear un caso, el estado queda en Pendiente de radicacion automaticamente.\n";
