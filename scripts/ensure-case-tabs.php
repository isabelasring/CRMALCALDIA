<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$tabs = ['Case', 'Contact', 'Email', 'Notification'];

foreach (['admin', 'julian.asignador', 'juan.inspeccion', 'edwin.radicacion'] as $uname) {
    $user = $em->getRDBRepository('User')->where(['userName' => $uname])->findOne();
    if (!$user) {
        continue;
    }
    $prefs = $em->getEntityById('Preferences', $user->getId());
    if (!$prefs) {
        continue;
    }
    $prefs->set('tabList', $tabs);
    $prefs->set('defaultTab', 'Case');
    $em->saveEntity($prefs);
    echo "$uname: tabList Case activo\n";
}

echo "Listo. Rebuild cache recomendado.\n";
