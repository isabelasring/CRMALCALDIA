<?php
require_once '/var/www/html/bootstrap.php';
$app = new Espo\Core\Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
foreach ($em->getRDBRepository('Notification')->where(['userId' => $julian->getId()])->find() as $n) {
    $n->set('read', false);
    $em->saveEntity($n);
    echo "Notif marcada no leida: " . substr(strip_tags((string) $n->get('message')), 0, 60) . "\n";
}
