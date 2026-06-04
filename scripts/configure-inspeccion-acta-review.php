<?php

/**
 * Inspección (Juan): leer acta, editar visto bueno y observaciones de revisión.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);

$roleNames = ['Inspección', 'Inspeccion'];

$actaRead = [
    'cActaFechaVisita',
    'cActaHoraVisita',
    'cActaDireccionVisita',
    'cActaNombreVisitado',
    'cActaDocumentoVisitado',
    'cActaHallazgos',
    'cActaMedidasTomadas',
    'cActaObservaciones',
    'cActaEstado',
    'cActaFechaAprobacion',
    'cActaRegistroOficial',
];

$reviewEdit = [
    'cActaVistoBueno',
    'cActaObservacionesRevision',
];

$cierreEdit = [
    'cCierreFecha',
    'cCierreResumen',
    'cCierreConclusiones',
    'cCierreMedidasAdoptadas',
    'cCierreObservaciones',
];

$cierreRead = [
    'cCierreEstado',
    'cCierreProcesoCompleto',
    'cCierreFechaRegistro',
];

foreach ($roleNames as $roleName) {
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

    $caseFields = array_keys($metadata->get(['entityDefs', 'Case', 'fields']) ?? []);

    foreach ($caseFields as $field) {
        if (in_array($field, $reviewEdit, true) || in_array($field, $cierreEdit, true)) {
            $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'yes'];
        } elseif (in_array($field, $actaRead, true) || in_array($field, $cierreRead, true)) {
            $fieldData['Case'][$field] = ['read' => 'yes', 'edit' => 'no'];
        }
    }

    // Lectura siempre; edición controlada por hook (visto bueno / Finalizado).
    $fieldData['Case']['status'] = ['read' => 'yes', 'edit' => 'yes'];

    $role->set('fieldData', $fieldData);

    $roleData = $role->get('data');

    if ($roleData instanceof stdClass) {
        $roleData = json_decode(json_encode($roleData), true);
    }

    if (!is_array($roleData)) {
        $roleData = [];
    }

    $roleData['Case'] = array_merge($roleData['Case'] ?? [], [
        'read' => 'all',
        'edit' => 'all',
        'create' => 'yes',
        'delete' => 'no',
        'stream' => 'all',
    ]);

    $role->set('data', $roleData);
    $em->saveEntity($role);

    echo "Rol {$roleName}: revisión acta + edición de casos configurada.\n";
}

echo "Listo.\n";
