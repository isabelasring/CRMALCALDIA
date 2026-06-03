<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(EntityManager::class);

$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

echo 'Julian roles: ' . implode(',', $user->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;
echo 'Julian teams: ' . implode(',', $user->getLinkMultipleIdList('teams') ?? []) . PHP_EOL;

$team = $em->getRDBRepository('Team')->where(['name' => 'Patrulleros'])->findOne();

if ($team) {
    echo PHP_EOL . 'Usuarios en equipo Patrulleros:' . PHP_EOL;
    foreach ($em->getRDBRepository('User')->where(['isActive' => true])->find() as $u) {
        $teams = $u->getLinkMultipleIdList('teams') ?? [];
        if (in_array($team->getId(), $teams, true)) {
            echo ' - ' . $u->get('userName') . ' (' . $u->get('name') . ')' . PHP_EOL;
        }
    }
}

echo PHP_EOL . 'Notificaciones Julian (Radicado):' . PHP_EOL;
foreach ($em->getRDBRepository('Notification')
    ->where(['userId' => $user->getId(), 'type' => 'Radicado'])
    ->order('createdAt', 'DESC')
    ->limit(0, 5)
    ->find() as $n) {
    echo ' - ' . $n->get('message') . PHP_EOL;
}

echo PHP_EOL . 'Casos visibles (todos / Radicado):' . PHP_EOL;
echo 'Total: ' . $em->getRDBRepository('Case')->count() . PHP_EOL;
echo 'Radicado: ' . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . PHP_EOL;
