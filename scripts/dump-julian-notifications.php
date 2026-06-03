<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
if (!$julian) {
    echo "julian no existe\n";
    exit(1);
}

echo "=== Notificaciones Julian ===\n";
foreach ($em->getRDBRepository('Notification')
    ->where(['userId' => $julian->getId()])
    ->order('number', 'DESC')
    ->limit(0, 10)
    ->find() as $n) {
    echo 'id=' . $n->getId() . ' type=' . $n->get('type') . ' read=' . ($n->get('read') ? '1' : '0') . "\n";
    echo '  msg: ' . substr(strip_tags((string) $n->get('message')), 0, 100) . "\n";
    $data = $n->get('data');
    echo '  data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
}

$acl = $app->getContainer()->get('aclManager');
echo 'Notification read ACL: ' . ($acl->check($julian, 'Notification', 'read') ? 'yes' : 'no') . "\n";
