<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\ApplicationRunners\Rebuilding;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
$patrullero = $em->getRDBRepository('User')->where(['userName' => 'patrullero.1'])->findOne();
$case = $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->findOne();

if (!$julian || !$patrullero || !$case) {
    echo "Falta julian, patrullero o caso Radicado\n";
    exit(1);
}

$app->setUser($julian);

$case->set('assignedUserId', $patrullero->getId());
$em->saveEntity($case);

$case = $em->getEntityById('Case', $case->getId());

echo 'Caso: ' . $case->get('name') . "\n";
echo 'Estado: ' . $case->get('status') . "\n";
echo 'Asignado: ' . $case->get('assignedUserName') . "\n";

if ($case->get('status') === 'En proceso') {
    echo "OK: En proceso para todos.\n";
    exit(0);
}

echo "ERROR: esperaba En proceso\n";
exit(1);
