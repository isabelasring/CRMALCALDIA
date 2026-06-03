<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $entityManager */
$entityManager = $app->getContainer()->getByClass(EntityManager::class);

$config = $app->getContainer()->getByClass(Espo\Core\Utils\Config::class);

$user = $entityManager
    ->getRDBRepository('User')
    ->where(['userName' => 'edwin.radicacion'])
    ->findOne();

if (!$user) {
    echo "Usuario edwin.radicacion no encontrado\n";
    exit(1);
}

echo 'Email Edwin: ' . ($user->get('emailAddress') ?: '(vacío)') . PHP_EOL;
echo 'Notificaciones email: ' . json_encode($user->get('emailNotificationsFlags') ?? []) . PHP_EOL;

$notifications = $entityManager
    ->getRDBRepository('Notification')
    ->where(['userId' => $user->getId()])
    ->order('createdAt', 'DESC')
    ->limit(0, 5)
    ->find();

echo PHP_EOL . 'Últimas notificaciones:' . PHP_EOL;

foreach ($notifications as $notification) {
    echo '- type=' . $notification->get('type')
        . ' emailProcessed=' . ($notification->get('emailIsProcessed') ? 'yes' : 'no')
        . ' read=' . ($notification->get('read') ? 'yes' : 'no')
        . PHP_EOL;
}

echo PHP_EOL . 'SMTP config:' . PHP_EOL;
echo 'smtpServer: ' . ($config->get('smtpServer') ?: '(no configurado)') . PHP_EOL;
echo 'smtpPort: ' . $config->get('smtpPort') . PHP_EOL;
echo 'smtpUsername: ' . ($config->get('smtpUsername') ?: '(vacío)') . PHP_EOL;
echo 'outboundEmailFromAddress: ' . ($config->get('outboundEmailFromAddress') ?: '(vacío)') . PHP_EOL;
