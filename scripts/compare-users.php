<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$acl = $app->getContainer()->get('aclManager');

foreach (['julian.asignador', 'juan.inspeccion', 'edwin.radicacion', 'admin'] as $uname) {
    $u = $em->getRDBRepository('User')->where(['userName' => $uname])->findOne();
    if (!$u) {
        echo "$uname: NO\n";
        continue;
    }
    echo "\n=== $uname ===\n";
    echo 'type: ' . ($u->get('type') ?: 'null') . "\n";
    echo 'roles: ' . implode(',', $u->getLinkMultipleIdList('roles') ?? []) . "\n";
    echo 'teams: ' . implode(',', $u->getLinkMultipleIdList('teams') ?? []) . "\n";
    echo 'Case read: ' . ($acl->check($u, 'Case', 'read') ? 'yes' : 'no') . "\n";
    echo 'Notification read: ' . ($acl->check($u, 'Notification', 'read') ? 'yes' : 'no') . "\n";
    $prefs = $em->getEntityById('Preferences', $u->getId());
    echo 'tabList: ' . json_encode($prefs?->get('tabList')) . "\n";
}
