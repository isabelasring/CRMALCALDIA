<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

foreach ($em->getRDBRepository('User')->find() as $u) {
    $name = strtolower((string) $u->get('userName') . ' ' . (string) $u->get('name'));
    if (str_contains($name, 'julian') || str_contains($name, 'asign')) {
        echo $u->get('userName') . ' | ' . $u->get('name') . ' | active=' . ($u->get('isActive') ? '1' : '0');
        echo ' | roles=' . implode(',', $u->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;
    }
}

echo PHP_EOL . 'Todos los usuarios:' . PHP_EOL;
foreach ($em->getRDBRepository('User')->find() as $u) {
    if ($u->get('type') === 'regular' || $u->get('type') === '') {
        echo '- ' . $u->get('userName') . ' (' . $u->get('name') . ')' . PHP_EOL;
    }
}
