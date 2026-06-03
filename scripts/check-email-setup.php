<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$entityManager = $app->getContainer()->getByClass(EntityManager::class);
$config = $app->getContainer()->getByClass(Espo\Core\Utils\Config::class);

$user = $entityManager
    ->getRDBRepository('User')
    ->where(['userName' => 'edwin.radicacion'])
    ->findOne();

echo '=== Edwin ===' . PHP_EOL;
echo 'emailAddress: ' . ($user->get('emailAddress') ?: '(vacío)') . PHP_EOL;

echo PHP_EOL . '=== Registros Email en CRM ===' . PHP_EOL;
echo 'Total emails: ' . $entityManager->getRDBRepository('Email')->count() . PHP_EOL;

$emails = $entityManager
    ->getRDBRepository('Email')
    ->order('createdAt', 'DESC')
    ->limit(0, 5)
    ->find();

foreach ($emails as $email) {
    echo '- ' . $email->get('name')
        . ' | status=' . $email->get('status')
        . ' | assignedUserId=' . ($email->get('assignedUserId') ?: '-')
        . PHP_EOL;
}

echo PHP_EOL . '=== Cuentas de correo grupal (InboundEmail) ===' . PHP_EOL;
foreach ($entityManager->getRDBRepository('InboundEmail')->find() as $account) {
    echo '- ' . $account->get('name')
        . ' | emailAddress=' . $account->get('emailAddress')
        . ' | status=' . $account->get('status')
        . ' | useImap=' . ($account->get('useImap') ? 'yes' : 'no')
        . ' | imapHost=' . ($account->get('imapHost') ?: '-')
        . ' | teams=' . implode(',', $account->getLinkMultipleIdList('teams') ?? [])
        . PHP_EOL;
}

echo PHP_EOL . '=== Cuentas personales de email ===' . PHP_EOL;
foreach ($entityManager->getRDBRepository('EmailAccount')->find() as $account) {
    echo '- userId=' . $account->get('assignedUserId')
        . ' | emailAddress=' . $account->get('emailAddress')
        . ' | status=' . $account->get('status')
        . PHP_EOL;
}

echo PHP_EOL . '=== SMTP saliente ===' . PHP_EOL;
echo 'smtpServer: ' . ($config->get('smtpServer') ?: '(no)') . PHP_EOL;

echo PHP_EOL . '=== Notificaciones Edwin ===' . PHP_EOL;
echo 'Total: ' . $entityManager
    ->getRDBRepository('Notification')
    ->where(['userId' => $user->getId()])
    ->count() . PHP_EOL;
