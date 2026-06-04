<?php

/**
 * Equipo Recibidores, Juan en el equipo, y campos cRecibidaPor / cRemitidoA como desplegable (Usuario).
 *
 * docker cp scripts/configure-recibidores.php espocrm:/tmp/configure-recibidores.php
 * docker cp espocrm-custom/. espocrm:/var/www/html/custom/Espo/Custom/
 * docker exec espocrm php /tmp/configure-recibidores.php
 * docker exec espocrm php command.php rebuild
 * docker exec espocrm php command.php clear-cache
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\ORM\EntityManager;

$teamName = 'Recibidores';
$defaultUserName = 'juan.inspeccion';
$varcharColumns = ['c_recibida_por', 'c_remitido_a'];
$caseEntityDefsPath = '/var/www/html/custom/Espo/Custom/Resources/metadata/entityDefs/Case.json';

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

echo "=== Equipo {$teamName} ===\n";

$pdo = $em->getPDO();

$teamStmt = $pdo->prepare(
    'SELECT id FROM team WHERE name = :name AND deleted = false LIMIT 1'
);
$teamStmt->execute(['name' => $teamName]);
$teamId = $teamStmt->fetchColumn();

if (!$teamId) {
    $teamId = substr(bin2hex(random_bytes(9)), 0, 17);
    $now = date('Y-m-d H:i:s');
    $insertTeam = $pdo->prepare(
        'INSERT INTO team (id, name, deleted, created_at, modified_at)
         VALUES (:id, :name, false, :now, :now)'
    );
    $insertTeam->execute(['id' => $teamId, 'name' => $teamName, 'now' => $now]);
    echo "Equipo {$teamName} creado.\n";
} else {
    echo "Equipo {$teamName} ya existe.\n";
}

$userStmt = $pdo->prepare(
    'SELECT id FROM "user" WHERE user_name = :userName AND deleted = false LIMIT 1'
);
$userStmt->execute(['userName' => $defaultUserName]);
$userId = $userStmt->fetchColumn();

if (!$userId) {
    echo "Usuario {$defaultUserName} no encontrado. Créalo en Administración → Usuarios.\n";
    exit(1);
}

$linkStmt = $pdo->prepare(
    'SELECT id FROM team_user
     WHERE team_id = :teamId AND user_id = :userId AND deleted = false LIMIT 1'
);
$linkStmt->execute(['teamId' => $teamId, 'userId' => $userId]);

if (!$linkStmt->fetchColumn()) {
    $insertLink = $pdo->prepare(
        'INSERT INTO team_user (team_id, user_id, deleted) VALUES (:teamId, :userId, false)'
    );
    $insertLink->execute(['teamId' => $teamId, 'userId' => $userId]);
    echo "{$defaultUserName} agregado al equipo {$teamName}.\n";
} else {
    echo "{$defaultUserName} ya está en {$teamName}.\n";
}

echo PHP_EOL . "=== Campos desplegables (Usuario) ===\n";

if (!is_readable($caseEntityDefsPath)) {
    echo "No se encontró {$caseEntityDefsPath}. Despliega espocrm-custom primero.\n";
    exit(1);
}

$entityDefs = json_decode((string) file_get_contents($caseEntityDefsPath), true);

if (!is_array($entityDefs)) {
    echo "Case.json inválido.\n";
    exit(1);
}

foreach (['cRecibidaPor', 'cRemitidoA'] as $fieldName) {
    $type = $entityDefs['fields'][$fieldName]['type'] ?? null;

    if ($type === 'link') {
        echo "{$fieldName}: ya es enlace a Usuario.\n";
    } else {
        echo "{$fieldName}: se convertirá a enlace a Usuario en rebuild.\n";
    }
}

foreach ($varcharColumns as $column) {
    $check = $pdo->prepare(
        "SELECT 1 FROM information_schema.columns
         WHERE table_name = 'case' AND column_name = :column LIMIT 1"
    );
    $check->execute(['column' => $column]);

    if ($check->fetchColumn()) {
        $pdo->exec('ALTER TABLE "case" DROP COLUMN IF EXISTS "' . $column . '"');
        echo "Columna {$column} eliminada.\n";
    }
}

$app->getContainer()->getByClass(DataManager::class)->rebuild();
echo PHP_EOL . "Rebuild completado.\n";
echo "Listo. En el formulario «Recibida por» y «Remitido a» solo aparecerán usuarios del equipo {$teamName}.\n";
