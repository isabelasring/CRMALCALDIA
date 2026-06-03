<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$config = $app->getContainer()->get('config');

echo "siteUrl: " . $config->get('siteUrl') . PHP_EOL;
echo "version: " . $config->get('version') . PHP_EOL;

echo "\n=== Casos en BD ===\n";
foreach ($em->getRDBRepository('Case')->find() as $c) {
    echo $c->getId() . ' | deleted=' . ($c->get('deleted') ? '1' : '0');
    echo ' | status=' . $c->get('status') . ' | ' . $c->get('name') . PHP_EOL;
}

echo "\n=== Admin user ===\n";
$admin = $em->getRDBRepository('User')->where(['userName' => 'admin'])->findOne();
if ($admin) {
    echo 'id=' . $admin->getId() . ' isAdmin=' . ($admin->get('isAdmin') ? '1' : '0') . ' active=' . ($admin->get('isActive') ? '1' : '0') . PHP_EOL;
}

$realAdmin = null;
foreach ($em->getRDBRepository('User')->find() as $u) {
    if ($u->get('isAdmin')) {
        echo 'Admin real: ' . $u->get('userName') . ' (' . $u->get('name') . ')' . PHP_EOL;
        $realAdmin = $u;
    }
}

if ($realAdmin) {
    $acl = $app->getContainer()->get('aclManager');
    echo 'Admin Case read: ' . ($acl->check($realAdmin, 'Case', 'read') ? 'yes' : 'no') . PHP_EOL;
}

echo "\n=== Layout list Case ===\n";
$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);
$list = $metadata->get(['layouts', 'Case', 'list']);
echo $list ? json_encode($list) : 'NULL (usa default)';

echo "\n\n=== selectDefs Case mandatory ===\n";
$sd = $metadata->get(['selectDefs', 'Case', 'accessControlFilterClassNameMap', 'mandatory']);
echo $sd ?: 'CRM default';
