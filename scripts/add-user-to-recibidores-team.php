<?php

/**
 * Agrega un usuario al equipo Recibidores.
 * Uso: php add-user-to-recibidores-team.php nombre.usuario
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$userName = $argv[1] ?? null;

if (!$userName) {
    echo "Uso: php add-user-to-recibidores-team.php <userName>\n";
    echo "Ejemplo: php add-user-to-recibidores-team.php juan.inspeccion\n";
    exit(1);
}

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$user = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();
$team = $em->getRDBRepository('Team')->where(['name' => 'Recibidores'])->findOne();

if (!$user) {
    echo "Usuario {$userName} no existe.\n";
    exit(1);
}

if (!$team) {
    echo "Equipo Recibidores no existe. Ejecuta configure-recibidores.php\n";
    exit(1);
}

$teams = $user->getLinkMultipleIdList('teams') ?? [];

if (in_array($team->getId(), $teams, true)) {
    echo "{$userName} ya está en Recibidores.\n";
    exit(0);
}

$teams[] = $team->getId();
$user->setLinkMultipleIdList('teams', $teams);
$em->saveEntity($user);

echo "Listo: {$userName} agregado al equipo Recibidores.\n";
