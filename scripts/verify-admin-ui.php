<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$metadata = $app->getContainer()->get('metadata');

$admin = $em->getRDBRepository('User')->where(['userName' => 'admin'])->findOne();
echo 'admin isAdmin: ' . var_export($admin?->get('isAdmin'), true) . PHP_EOL;
echo 'admin isActive: ' . var_export($admin?->get('isActive'), true) . PHP_EOL;

$scope = $metadata->get(['scopes', 'Case']);
echo 'Case scope disabled: ' . var_export($scope['disabled'] ?? false, true) . PHP_EOL;
echo 'Case tab: ' . var_export($scope['tab'] ?? true, true) . PHP_EOL;

$prefs = $em->getEntityById('Preferences', $admin->getId());
echo 'admin tabList: ' . json_encode($prefs?->get('tabList')) . PHP_EOL;
echo 'admin dashboardLayout: ' . (strlen((string) $prefs?->get('dashboardLayout')) > 5 ? 'SET' : 'empty') . PHP_EOL;

echo 'Total cases DB: ' . $em->getRDBRepository('Case')->count() . PHP_EOL;
