<?php

chdir('/var/www/html');
require_once 'bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\PasswordHash;
use Espo\ORM\EntityManager;

try {
    $app = new Application();
    $app->setupSystemUser();
    $em = $app->getContainer()->getByClass(EntityManager::class);

    $user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
    echo "User id: " . $user->getId() . "\n";
    echo "Roles: " . implode(',', $user->getLinkMultipleIdList('roles') ?? []) . "\n";

    $acl = $app->getContainer()->get('aclManager');
    echo 'Case read: ' . ($acl->check($user, 'Case', 'read') ? 'yes' : 'no') . "\n";
    echo 'Notification read: ' . ($acl->check($user, 'Notification', 'read') ? 'yes' : 'no') . "\n";

    $case = $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->findOne();
    if ($case) {
        echo 'checkEntityRead: ' . ($acl->checkEntityRead($user, $case) ? 'yes' : 'no') . "\n";
    }

    $notifCount = $em->getRDBRepository('Notification')->where(['userId' => $user->getId()])->count();
    echo "Notifications in DB: $notifCount\n";

    foreach ($em->getRDBRepository('Notification')->where(['userId' => $user->getId()])->find() as $n) {
        echo ' - type=' . $n->get('type') . ' read=' . ($n->get('read') ? '1' : '0') . "\n";
    }

    // Password verify
    $ok = (new PasswordHash())->verify('Julian2026!', $user->get('password'));
    echo 'Password Julian2026! valid: ' . ($ok ? 'yes' : 'no') . "\n";

    $role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
    $fd = $role->get('fieldData');
    echo 'fieldData empty: ' . (empty((array) $fd) || $fd === (object) [] ? 'yes' : 'no') . "\n";

} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
