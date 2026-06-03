<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

try {
    $app = new Application();
    $app->setupSystemUser();
    $em = $app->getContainer()->getByClass(EntityManager::class);
    $m = $app->getContainer()->get('metadata');

    $requiredBlock = $m->get(['clientDefs', 'Case', 'dynamicLogic', 'fields', 'assignedUser', 'required']);
    echo 'assignedUser required block: ' . json_encode($requiredBlock) . "\n";

    $case = $em->getRDBRepository('Case')->getNew();
    $case->set('name', 'Prueba guardado');
    $case->set('cNombreDelPeticionario', 'Test');
    $case->set('cApellido', 'Usuario');
    $case->set('cTipoDeDocumento', 'CC');
    $case->set('cNumeroDeDocumento', '999888777');
    $case->set('cCanalDeReporte', 'Teléfono');
    $case->set('cTelefono', '+573001234567');
    $case->set('cMunicipio', 'Envigado');
    $case->set('priority', 'Normal');
    $case->set('type', 'Incident');
    $case->set('status', 'Pendiente de radicacion');

    $em->saveEntity($case);
    echo 'OK id=' . $case->getId() . ' status=' . $case->get('status') . "\n";
    $em->removeEntity($case);
    echo "Eliminado.\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
