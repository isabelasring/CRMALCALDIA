<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Mail\EmailSender;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);
$emailSender = $app->getContainer()->getByClass(EmailSender::class);
$config = $app->getContainer()->getByClass(Espo\Core\Utils\Config::class);

$juan = $em->getRDBRepository('User')->where(['userName' => 'juan.inspeccion'])->findOne();
$edwin = $em->getRDBRepository('User')->where(['userName' => 'edwin.radicacion'])->findOne();

echo 'Juan id: ' . ($juan?->getId() ?: 'NO') . PHP_EOL;
echo 'Juan email: ' . ($juan?->get('emailAddress') ?: '(vacío)') . PHP_EOL;
echo 'Juan roles: ' . implode(',', $juan?->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;

echo 'SMTP: ' . ($config->get('smtpServer') ?: 'NO') . PHP_EOL;
echo 'hasSystemSmtp: ' . ($emailSender->hasSystemSmtp() ? 'yes' : 'no') . PHP_EOL;

echo PHP_EOL . '=== Casos Radicado ===' . PHP_EOL;

foreach ($em->getRDBRepository('Case')->where(['status' => 'Radicado'])->find() as $case) {
    echo '- ' . $case->get('name') . PHP_EOL;
    echo '  cNumeroRadicacion: ' . ($case->get('cNumeroRadicacion') ?: '(vacío)') . PHP_EOL;
    echo '  modifiedAt: ' . $case->get('modifiedAt') . PHP_EOL;
}

if ($juan) {
    echo PHP_EOL . '=== Notificaciones de Juan ===' . PHP_EOL;
    $notifications = $em->getRDBRepository('Notification')
        ->where(['userId' => $juan->getId()])
        ->order('createdAt', 'DESC')
        ->limit(0, 10)
        ->find();

    if (count($notifications) === 0) {
        echo '(ninguna)' . PHP_EOL;
    }

    foreach ($notifications as $n) {
        echo '- type=' . $n->get('type')
            . ' read=' . ($n->get('read') ? 'y' : 'n')
            . ' data=' . json_encode($n->get('data'))
            . PHP_EOL;
    }
}

echo PHP_EOL . 'Hook class: '
    . (class_exists(\Espo\Custom\Hooks\CaseObj\NotifyCaseRadicado::class) ? 'OK' : 'MISSING')
    . PHP_EOL;
