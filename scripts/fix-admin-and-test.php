<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

echo "=== Usuarios type=admin ===\n";
foreach ($em->getRDBRepository('User')->find() as $u) {
    if (in_array($u->get('type'), ['admin', 'super-admin'], true)) {
        echo $u->get('userName') . ' type=' . $u->get('type') . PHP_EOL;
    }
}

$admin = $em->getRDBRepository('User')->where(['userName' => 'admin'])->findOne();
if ($admin) {
    $admin->set('type', 'admin');
    $admin->set('isActive', true);
    $admin->set('password', password_hash('AlcaldiaAdmin2026!', PASSWORD_BCRYPT));
    $em->saveEntity($admin, ['silent' => true]);
    echo "\nadmin: type=admin, password=AlcaldiaAdmin2026!\n";
}

$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
echo 'julian type: ' . ($julian->get('type') ?: 'regular') . PHP_EOL;

$acl = $app->getContainer()->get('aclManager');
$admin = $em->getRDBRepository('User')->where(['userName' => 'admin'])->findOne();
echo 'admin Case read: ' . ($acl->check($admin, 'Case', 'read') ? 'yes' : 'no') . PHP_EOL;
echo 'julian Case read: ' . ($acl->check($julian, 'Case', 'read') ? 'yes' : 'no') . PHP_EOL;
