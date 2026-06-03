<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$m = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);

$status = $m->get(['entityDefs', 'Case', 'fields', 'status']);
echo 'options: ' . json_encode($status['options'] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo 'notActualOptions: ' . json_encode($status['notActualOptions'] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;

// Field level ACL table test
$table = $app->getContainer()->get('aclManager')->getTable($app->getContainer()->get('user'));
echo PHP_EOL . 'Testing with julian...' . PHP_EOL;

$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

$aclManager = $app->getContainer()->get('aclManager');
$acl = $aclManager->create($julian, false);

echo 'Case scope read: ' . ($acl->check('Case', 'read') ? 'yes' : 'no') . PHP_EOL;
echo 'Notification scope read: ' . ($acl->check('Notification', 'read') ? 'yes' : 'no') . PHP_EOL;

$case = $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->findOne();
if ($case) {
    echo 'checkEntityRead: ' . ($acl->checkEntityRead($case) ? 'yes' : 'no') . PHP_EOL;
}
