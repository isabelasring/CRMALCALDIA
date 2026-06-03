<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$config = $app->getContainer()->getByClass(Config::class);
$entityManager = $app->getContainer()->getByClass(EntityManager::class);
$emailSender = $app->getContainer()->getByClass(EmailSender::class);

echo 'From address: ' . ($config->get('outboundEmailFromAddress') ?: '(vacío)') . PHP_EOL;
echo 'Has system SMTP: ' . ($emailSender->hasSystemSmtp() ? 'yes' : 'no') . PHP_EOL;

$accounts = $entityManager
    ->getRDBRepository('InboundEmail')
    ->where([
        'status' => 'Active',
        'useSmtp' => true,
    ])
    ->find();

echo PHP_EOL . 'Cuentas grupales SMTP:' . PHP_EOL;

foreach ($accounts as $account) {
    echo '- ' . $account->get('emailAddress')
        . ' host=' . ($account->get('smtpHost') ?: '(vacío)')
        . ' isSystem=' . ($account->get('isSystem') ? 'yes' : 'no')
        . PHP_EOL;
}
