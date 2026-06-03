<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\InjectableFactory;
use Espo\ORM\EntityManager;
use Espo\Tools\EmailNotification\AssignmentProcessor;
use Espo\Tools\EmailNotification\AssignmentProcessorData;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $entityManager */
$entityManager = $app->getContainer()->getByClass(EntityManager::class);

/** @var InjectableFactory $injectableFactory */
$injectableFactory = $app->getContainer()->getByClass(InjectableFactory::class);
$assignmentProcessor = $injectableFactory->create(AssignmentProcessor::class);

$status = 'Pendiente de radicacion';
$sent = 0;
$errors = 0;

$cases = $entityManager
    ->getRDBRepository('Case')
    ->where([
        'status' => $status,
        'assignedUserId!=' => null,
    ])
    ->find();

foreach ($cases as $case) {
    $assignedUserId = $case->get('assignedUserId');

    if (!$assignedUserId) {
        continue;
    }

    try {
        $assignmentProcessor->process(
            AssignmentProcessorData::create()
                ->withUserId($assignedUserId)
                ->withAssignerUserId($case->get('createdById') ?: $assignedUserId)
                ->withEntityId($case->getId())
                ->withEntityType($case->getEntityType())
        );
        $sent++;
        echo 'Enviado: ' . $case->get('name') . PHP_EOL;
    } catch (Throwable $e) {
        $errors++;
        echo 'Error en ' . $case->get('name') . ': ' . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "Intentos OK: {$sent}, errores: {$errors}" . PHP_EOL;
