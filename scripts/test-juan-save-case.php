<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$juan = $em->getRDBRepositoryByClass(User::class)
    ->where(['userName' => 'juan.inspeccion'])
    ->findOne();

if (!$juan) {
    echo "juan.inspeccion no existe\n";
    exit(1);
}

$GLOBALS['app'] = $app;
$app->getContainer()->remove('user');
$app->getContainer()->set('user', $juan);

$case = $em->getRDBRepository('Case')->getNew();
$case->set('name', 'Prueba guardado Juan');
$case->set('cNombreDelPeticionario', 'Test');
$case->set('cApellido', 'Usuario');
$case->set('cTipoDeDocumento', 'CC');
$case->set('cNumeroDeDocumento', '999888777');
$case->set('cCanalDeReporte', 'Teléfono');
$case->set('cTelefono', '+573001234567');
$case->set('cMunicipio', 'Envigado');
$case->set('priority', 'Normal');
$case->set('type', 'Incident');

try {
    $em->saveEntity($case);
    echo 'OK id=' . $case->getId() . ' status=' . $case->get('status') . "\n";
    $em->removeEntity($case);
    echo "Caso de prueba eliminado.\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
