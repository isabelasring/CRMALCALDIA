<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\PasswordHash;

try {
    $app = new Application();
    $app->setupSystemUser();
    $em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
    $user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
    if (!$user) {
        echo "No existe\n";
        exit(1);
    }
    $hash = (new PasswordHash())->hash('Julian2026!');
    $user->set('password', $hash);
    $em->saveEntity($user, ['silent' => true]);
    echo "OK password Julian2026!\n";
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . PHP_EOL;
}
