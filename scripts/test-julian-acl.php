<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
$aclManager = $app->getContainer()->get('aclManager');

echo 'Case readAll: ' . ($aclManager->checkReadAll($julian, 'Case') ? 'yes' : 'no') . PHP_EOL;
echo 'Case check read: ' . ($aclManager->check($julian, 'Case', 'read') ? 'yes' : 'no') . PHP_EOL;
echo 'Notification read: ' . ($aclManager->check($julian, 'Notification', 'read') ? 'yes' : 'no') . PHP_EOL;

$case = $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->findOne();
if ($case) {
    echo 'entity read: ' . ($aclManager->checkEntityRead($julian, $case) ? 'yes' : 'no') . PHP_EOL;
    echo 'entity edit: ' . ($aclManager->checkEntityEdit($julian, $case) ? 'yes' : 'no') . PHP_EOL;
}
