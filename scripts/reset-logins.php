<?php

/**
 * Restaura acceso admin y Julian con contraseñas del .env
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$users = [
    ['userName' => 'admin', 'password' => 'AlcaldiaAdmin2026!', 'type' => 'admin'],
    ['userName' => 'julian.asignador', 'password' => 'Julian2026!', 'type' => 'regular'],
];

foreach ($users as $cfg) {
    $user = $em->getRDBRepository('User')->where(['userName' => $cfg['userName']])->findOne();
    if (!$user) {
        echo $cfg['userName'] . ": NO EXISTE\n";
        continue;
    }
    $user->set('password', password_hash($cfg['password'], PASSWORD_BCRYPT));
    $user->set('type', $cfg['type']);
    $user->set('isActive', true);
    $em->saveEntity($user, ['silent' => true]);

    $prefs = $em->getEntityById('Preferences', $user->getId());
    if ($prefs) {
        $prefs->set('tabList', null);
        $prefs->set('defaultTab', null);
        $em->saveEntity($prefs);
    }

    echo $cfg['userName'] . ' OK | type=' . $cfg['type'] . " | pass=" . $cfg['password'] . "\n";
}

echo "\nAPI devuelve 2 casos. Si la pantalla sigue vacia: borra cookies de localhost:8080\n";
