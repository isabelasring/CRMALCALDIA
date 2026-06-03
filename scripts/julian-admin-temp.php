<?php

/**
 * TEMPORAL: pone Julian como admin para probar si ve casos.
 * Ejecutar julian-admin-temp.php revert después.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
$revert = ($argv[1] ?? '') === 'revert';

if ($revert) {
    $user->set('isAdmin', false);
    echo "Julian ya NO es admin.\n";
} else {
    $user->set('isAdmin', true);
    echo "Julian es ADMIN temporalmente (para prueba).\n";
}

$user->set('password', password_hash('Julian2026!', PASSWORD_BCRYPT));
$em->saveEntity($user, ['silent' => true]);
echo "Password: Julian2026!\n";
