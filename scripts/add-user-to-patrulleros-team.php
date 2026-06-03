<?php

/**
 * Agrega un usuario al equipo Patrulleros.
 * Uso: php add-user-to-patrulleros-team.php nombre.usuario
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$userName = $argv[1] ?? null;

if (!$userName) {
    echo "Uso: php add-user-to-patrulleros-team.php <userName>\n";
    echo "Ejemplo: php add-user-to-patrulleros-team.php carlos.patrullero\n";
    exit(1);
}

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$user = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();
$team = $em->getRDBRepository('Team')->where(['name' => 'Patrulleros'])->findOne();

if (!$user) {
    echo "Usuario {$userName} no existe. Créalo en Administración → Usuarios.\n";
    exit(1);
}

if (!$team) {
    echo "Equipo Patrulleros no existe. Ejecuta configure-asignador-patrulleros.php\n";
    exit(1);
}

$teams = $user->getLinkMultipleIdList('teams') ?? [];

if (in_array($team->getId(), $teams, true)) {
    echo "{$userName} ya está en Patrulleros.\n";
    exit(0);
}

$teams[] = $team->getId();
$user->setLinkMultipleIdList('teams', $teams);
$em->saveEntity($user);

echo "Listo: {$userName} agregado al equipo Patrulleros.\n";
echo "Julian ya puede asignarle casos en «Asignado a».\n";
