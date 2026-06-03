<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Mail\EmailSender;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);
$emailSender = $app->getContainer()->getByClass(EmailSender::class);

echo 'Hook NotifyCaseRadicado: '
    . (class_exists(\Espo\Custom\Hooks\CaseObj\NotifyCaseRadicado::class) ? 'SI' : 'NO')
    . PHP_EOL;

$role = $em->getRDBRepository('Role')->where(['name' => 'Inspección'])->findOne();
echo 'Rol Inspección: ' . ($role ? 'SI (' . $role->getId() . ')' : 'NO — créalo') . PHP_EOL;

if ($role) {
    $data = (array) ($role->get('data') instanceof stdClass
        ? json_decode(json_encode($role->get('data')), true)
        : $role->get('data'));
    echo 'Case read: ' . ($data['Case']['read'] ?? '-') . ' edit: ' . ($data['Case']['edit'] ?? '-') . PHP_EOL;
}

echo 'SMTP activo: ' . ($emailSender->hasSystemSmtp() ? 'SI' : 'NO') . PHP_EOL;
