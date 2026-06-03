<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
echo 'assignmentPermission: ' . $role->get('assignmentPermission') . PHP_EOL;

$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
$prefs = $em->getRDBRepository('Preferences')->where(['userId' => $user->getId()])->findOne();
echo 'tabList prefs: ' . json_encode($prefs?->get('tabList')) . PHP_EOL;
