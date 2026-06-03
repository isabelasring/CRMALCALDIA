<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$case = $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->findOne();
$patrullero = $em->getRDBRepository('User')->where(['userName' => 'patrullero.1'])->findOne();

if (!$case || !$patrullero) {
    echo "Falta caso o patrullero\n";
    exit(1);
}

$case->setAsFetched();
$case->set('description', 'cambio de prueba que debe revertirse');
$case->set('assignedUserId', $patrullero->getId());

if (!method_exists($case, 'clearAttributeChange')) {
    echo "Entity ya no usa clearAttributeChange (OK Espo 9)\n";
}

if ($case->hasFetched('description')) {
    $case->set('description', $case->getFetched('description'));
    echo 'description revertido=' . ($case->isAttributeChanged('description') ? 'NO' : 'SI') . "\n";
}

echo 'assignedUserId changed=' . ($case->isAttributeChanged('assignedUserId') ? 'SI' : 'NO') . "\n";
echo "Hook corregido en contenedor.\n";
