<?php
require_once '/var/www/html/bootstrap.php';
use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$acl = $app->getContainer()->get('aclManager');
$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
$data = $role->get('data');
if ($data instanceof stdClass) {
    $data = json_decode(json_encode($data), true);
}
echo 'userPermission=' . $role->get('userPermission') . "\n";
echo 'User scope=' . json_encode($data['User'] ?? null) . "\n";
foreach (['patrullero.1', 'patrullero.2'] as $u) {
    $user = $em->getRDBRepository('User')->where(['userName' => $u])->findOne();
    echo "$u visible=" . ($acl->checkEntityRead($julian, $user) ? 'SI' : 'NO') . "\n";
}
