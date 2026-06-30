<?php

/**
 * Garantiza usuario admin con las credenciales de Dokploy / .env.
 * Se ejecuta en cada deploy (después del wipe si aplica).
 */

require_once '/var/www/html/bootstrap.php';
require_once __DIR__ . '/includes/admin-credentials.php';

use Espo\Core\Application;
use Espo\Core\Authentication\Password\PasswordHasherFactory;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$pdo = $em->getPDO();

$userName = alcaldiaAdminUsername();
$password = alcaldiaAdminPassword();

if ($userName === '') {
    echo 'ERROR: ESPOCRM_ADMIN_USERNAME vacío.' . PHP_EOL;
    exit(1);
}

if ($password === '') {
    echo 'ERROR: ESPOCRM_ADMIN_PASSWORD vacía. Revisa Dokploy → Environment.' . PHP_EOL;
    exit(1);
}

$hasher = $app->getContainer()->getByClass(PasswordHasherFactory::class)->create();
$passwordHash = $hasher->hash($password);

$user = $em->getRDBRepository('User')->where(['userName' => $userName])->findOne();

if (!$user) {
    $user = $em->getRDBRepository('User')->getNew();
    $user->set('userName', $userName);
    $user->set('firstName', 'Administrador');
    $user->set('lastName', 'Sistema');
    echo "Usuario admin creado: {$userName}" . PHP_EOL;
} else {
    echo "Usuario admin actualizado: {$userName}" . PHP_EOL;
}

$user->set('type', 'admin');
$user->set('password', $passwordHash);
$user->set('isActive', true);
$user->set('authMethod', null);

$em->saveEntity($user, ['skipHooks' => true]);

$userId = $user->getId();

if (!$userId) {
    echo 'ERROR: no se pudo obtener id del usuario admin.' . PHP_EOL;
    exit(1);
}

$prefs = $em->getEntityById('Preferences', $userId);

if (!$prefs) {
    $prefs = $em->getEntity('Preferences');
    $prefs->set('id', $userId);
}

$prefs->set('tabList', null);
$prefs->set('useCustomTabList', false);
$em->saveEntity($prefs, ['skipHooks' => true]);

try {
    $pdo->exec('DELETE FROM auth_token');
} catch (Throwable $exception) {
    // Tabla puede estar vacía tras wipe.
}

$stored = $em->getRDBRepository('User')->getById($userId);
$storedHash = $stored ? trim((string) $stored->get('password')) : '';

if ($storedHash === '') {
    echo 'ERROR: el admin quedó sin contraseña en BD.' . PHP_EOL;
    exit(1);
}

$adminCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM \"user\" WHERE deleted = false AND type = 'admin' AND is_active = true"
)->fetchColumn();

if ($adminCount < 1) {
    echo 'ERROR: no hay admin activo en la base de datos.' . PHP_EOL;
    exit(1);
}

echo "Admin listo (id={$userId}, admins_activos={$adminCount})." . PHP_EOL;
echo "Ingresa con usuario «{$userName}» y la contraseña de ESPOCRM_ADMIN_PASSWORD." . PHP_EOL;
