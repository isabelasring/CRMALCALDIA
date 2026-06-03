<?php
require_once '/var/www/html/bootstrap.php';
$app = new Espo\Core\Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
foreach (['admin', 'julian.asignador'] as $u) {
    $user = $em->getRDBRepository('User')->where(['userName' => $u])->findOne();
    echo "$u type={$user->get('type')} isAdmin()=" . ($user->isAdmin() ? 'yes' : 'no') . "\n";
}
