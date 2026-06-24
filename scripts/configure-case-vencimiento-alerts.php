<?php

/**
 * Activa el job programado de alertas de vencimiento y opcionalmente lo ejecuta.
 *
 * docker cp scripts/configure-case-vencimiento-alerts.php espocrm:/tmp/
 * docker exec espocrm php /tmp/configure-case-vencimiento-alerts.php
 * docker exec espocrm php command.php run-job CheckCaseVencimientoAlerts
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$jobName = 'CheckCaseVencimientoAlerts';
$scheduling = '0 7 * * *';

$job = $em->getRDBRepository('ScheduledJob')
    ->where(['job' => $jobName])
    ->findOne();

if (!$job) {
    $job = $em->getRDBRepository('ScheduledJob')->getNew();
    $job->set('job', $jobName);
    $job->set('name', 'Alertas de vencimiento de casos');
    $job->set('isInternal', true);
    echo "Scheduled job creado: {$jobName}\n";
} else {
    echo "Scheduled job encontrado: {$jobName}\n";
}

$job->set('status', 'Active');
$job->set('scheduling', $scheduling);
$em->saveEntity($job);

echo "OK status=Active scheduling={$scheduling}\n";
echo "Para probar ahora: php command.php run-job {$jobName}\n";
