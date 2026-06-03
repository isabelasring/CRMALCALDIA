<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;

try {
    $app = new Application();
    $app->setupSystemUser();
    $em = $app->getContainer()->getByClass(EntityManager::class);
    $config = $app->getContainer()->getByClass(Config::class);

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $config->get('database.host'),
        $config->get('database.port'),
        $config->get('database.dbname')
    );
    $pdo = new PDO(
        $dsn,
        $config->get('database.user'),
        $config->get('database.password')
    );

    $tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename ILIKE '%user%'")
        ->fetchAll(PDO::FETCH_COLUMN);
    echo 'tables: ' . implode(', ', $tables) . "\n";

    $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'user' ORDER BY ordinal_position")
        ->fetchAll(PDO::FETCH_COLUMN);
    echo 'user columns: ' . implode(', ', $cols) . "\n";

    $stmt = $pdo->query("SELECT id, user_name, is_admin, is_active FROM \"user\" WHERE deleted = false AND user_name = 'admin' LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'ANTES: ' . json_encode($row) . "\n";

    $hash = password_hash('AlcaldiaAdmin2026!', PASSWORD_BCRYPT);
    $upd = $pdo->prepare('UPDATE "user" SET is_admin = true, is_active = true, password = :p WHERE user_name = :u');
    $upd->execute(['p' => $hash, 'u' => 'admin']);

    $stmt = $pdo->query("SELECT id, user_name, is_admin, is_active FROM \"user\" WHERE deleted = false AND user_name = 'admin' LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'DESPUES: ' . json_encode($row) . "\n";

    $admin = $em->getRDBRepository('User')->where(['userName' => 'admin'])->findOne();
    echo 'ORM isAdmin: ' . var_export($admin->get('isAdmin'), true) . "\n";
    echo 'cases=' . $em->getRDBRepository('Case')->count() . "\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
