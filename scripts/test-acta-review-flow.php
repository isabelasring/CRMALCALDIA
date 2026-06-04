<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$patrullero = $em->getRDBRepositoryByClass(User::class)
    ->where(['userName' => 'patrullero.1'])
    ->findOne();

$juan = $em->getRDBRepositoryByClass(User::class)
    ->where(['userName' => 'juan.inspeccion'])
    ->findOne();

$case = $em->getRDBRepository('Case')
    ->where([
        'status' => 'En proceso',
        'assignedUserId' => $patrullero->getId(),
    ])
    ->findOne();

if (!$case) {
    echo "No hay caso En proceso para patrullero.1\n";
    exit(1);
}

echo "=== Patrullero diligencia acta ===\n";
$app->getContainer()->remove('user');
$app->getContainer()->set('user', $patrullero);

$case->set('cActaFechaVisita', date('Y-m-d'));
$case->set('cActaHallazgos', 'Hallazgo de prueba automatizado');
$em->saveEntity($case);

$case = $em->getEntityById('Case', $case->getId());
echo 'status=' . $case->get('status') . ' acta=' . $case->get('cActaEstado')
    . ' oficial=' . ($case->get('cActaRegistroOficial') ? 'si' : 'no') . "\n";

if ($case->get('status') !== 'Visita realizada') {
    echo "ERROR patrullero\n";
    exit(1);
}

echo "=== Juan visto bueno ===\n";
$app->getContainer()->remove('user');
$app->getContainer()->set('user', $juan);

$case->set('cActaVistoBueno', true);
$case->set('cActaObservacionesRevision', 'Revisado OK');
$em->saveEntity($case);

$case = $em->getEntityById('Case', $case->getId());
echo 'status=' . $case->get('status') . ' acta=' . $case->get('cActaEstado')
    . ' oficial=' . ($case->get('cActaRegistroOficial') ? 'si' : 'no') . "\n";

if ($case->get('status') !== 'Visita aprobada' || !$case->get('cActaRegistroOficial')) {
    echo "ERROR juan visto bueno\n";
    exit(1);
}

echo "=== Juan finaliza caso ===\n";
$case->set('status', 'Finalizado');
$em->saveEntity($case);

$case = $em->getEntityById('Case', $case->getId());
echo 'status=' . $case->get('status') . "\n";

if ($case->get('status') === 'Finalizado') {
    echo "OK flujo completo.\n";
    exit(0);
}

echo "ERROR juan finalizar\n";
exit(1);
