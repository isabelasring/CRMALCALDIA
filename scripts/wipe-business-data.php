<?php

/**
 * Vacía todos los datos de negocio en PostgreSQL.
 * Conserva usuarios, roles, equipos, preferencias y configuración del sistema.
 *
 * Se ejecuta automáticamente una vez en el próximo deploy (ver deploy-custom-dokploy.sh).
 * Forzar de nuevo: ESPO_WIPE_BUSINESS_DATA=1
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$pdo = $em->getPDO();

/** Tablas de configuración / acceso que NO se vacían. */
$keepTables = [
    'user',
    'role',
    'role_user',
    'role_team',
    'team',
    'team_user',
    'preferences',
    'user_data',
    'user_working_time_range',
    'scheduled_job',
    'extension',
    'integration',
    'system_data',
    'layout_record',
    'layout_set',
    'currency',
    'currency_record',
    'currency_record_rate',
    'address_country',
    'portal',
    'portal_role',
    'portal_portal_role',
    'portal_role_user',
    'portal_user',
    'authentication_provider',
    'o_auth_provider',
    'email_template',
    'email_template_category',
    'email_template_category_path',
    'template',
    'inbound_email',
    'email_account',
    'email_filter',
    'webhook',
    'dashboard_template',
    'working_time_calendar',
    'working_time_range',
    'working_time_calendar_working_time_range',
    'next_number',
    'unique_id',
    'app_secret',
    'group_email_folder',
    'group_email_folder_team',
];

$quoteIdentifier = static function (string $name): string {
    return '"' . str_replace('"', '""', $name) . '"';
};

$stmt = $pdo->query(
    "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
);

$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
$toTruncate = [];

foreach ($allTables as $table) {
    if (!in_array($table, $keepTables, true)) {
        $toTruncate[] = $table;
    }
}

if ($toTruncate === []) {
    echo 'No hay tablas para vaciar.' . PHP_EOL;
    exit(0);
}

echo 'Vacía datos de negocio en ' . count($toTruncate) . ' tablas...' . PHP_EOL;

$pdo->beginTransaction();

try {
    $quoted = array_map($quoteIdentifier, $toTruncate);
    $sql = 'TRUNCATE TABLE ' . implode(', ', $quoted) . ' RESTART IDENTITY CASCADE';

    $pdo->exec($sql);

    $pdo->exec(
        "UPDATE next_number SET value = 1 WHERE entity_type IN (
            'Case', 'ActaVisita', 'ActuoArchivo', 'ComunicacionCaso', 'AsignacionHistorial',
            'Contact', 'Account', 'Document', 'Task', 'Lead', 'Opportunity'
        )"
    );

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $exception;
}

echo 'Datos de negocio eliminados. Usuarios, roles y configuración conservados.' . PHP_EOL;
echo 'Reinicia consecutivos en next_number.' . PHP_EOL;
