<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\User;

try {
    $app = new Application();
    $app->setupSystemUser();

    $em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
    $julian = $em->getRDBRepositoryByClass(User::class)->where(['userName' => 'julian.asignador'])->findOne();

    $aclManager = $app->getContainer()->getByClass(Espo\Core\AclManager::class);
    $acl = $aclManager->createForUser($julian);

    echo 'Case read: ' . ($acl->check('Case', 'read') ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
