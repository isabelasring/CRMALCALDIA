<?php

/**
 * EspoCRM 9: el administrador usa user.type = 'admin' (no is_admin).
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$users = [
    ['userName' => 'admin', 'type' => 'admin', 'password' => 'AlcaldiaAdmin2026!'],
    ['userName' => 'julian.asignador', 'type' => 'regular', 'password' => 'Julian2026!'],
];

foreach ($users as $cfg) {
    $user = $em->getRDBRepository('User')->where(['userName' => $cfg['userName']])->findOne();
    if (!$user) {
        echo $cfg['userName'] . ": NO EXISTE\n";
        continue;
    }

    echo $cfg['userName'] . ' type ANTES=' . ($user->get('type') ?: 'null') . "\n";

    $user->set('type', $cfg['type']);
    $user->set('isActive', true);
    $user->set('password', password_hash($cfg['password'], PASSWORD_BCRYPT));
    $em->saveEntity($user);

    $user = $em->getEntityById('User', $user->getId());
    echo $cfg['userName'] . ' type DESPUES=' . $user->get('type') . " pass=" . $cfg['password'] . "\n";
}

$acl = $app->getContainer()->get('aclManager');
$admin = $em->getRDBRepository('User')->where(['userName' => 'admin'])->findOne();
$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

echo "\nadmin isAdmin (legacy): " . var_export($admin->get('isAdmin'), true) . "\n";
echo 'admin Case read: ' . ($acl->check($admin, 'Case', 'read') ? 'yes' : 'no') . "\n";
echo 'julian Case read: ' . ($acl->check($julian, 'Case', 'read') ? 'yes' : 'no') . "\n";
echo 'Casos en BD: ' . $em->getRDBRepository('Case')->count() . "\n";
