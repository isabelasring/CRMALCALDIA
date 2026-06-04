<?php

/**
 * Borra TODOS los casos y datos relacionados (notas, notificaciones, vínculos).
 * No borra usuarios, roles, contactos ni configuración.
 *
 * docker cp scripts/wipe-case-data.php espocrm:/tmp/
 * docker exec espocrm php /tmp/wipe-case-data.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$pdo = $em->getPDO();

$counts = static function (string $sql) use ($pdo): int {
    return (int) $pdo->query($sql)->fetchColumn();
};

echo "=== Antes ===\n";
echo 'Casos (activos): ' . $counts('SELECT COUNT(*) FROM "case" WHERE deleted = false') . "\n";
echo 'Casos (total): ' . $counts('SELECT COUNT(*) FROM "case"') . "\n";

$pdo->beginTransaction();

try {
    $steps = [
        ['c_case_document', 'DELETE FROM c_case_document'],
        ['case_contact', 'DELETE FROM case_contact'],
        ['case_knowledge_base_article', 'DELETE FROM case_knowledge_base_article'],
        ['entity_team (Case)', 'DELETE FROM entity_team WHERE entity_type = \'Case\''],
        ['note (Case)', 'DELETE FROM note WHERE parent_type = \'Case\' OR related_type = \'Case\''],
        ['notification (Case)', 'DELETE FROM notification WHERE related_type = \'Case\''],
        ['case', 'DELETE FROM "case"'],
    ];

    foreach ($steps as [$label, $sql]) {
        $deleted = $pdo->exec($sql);
        echo "OK {$label}: {$deleted} filas\n";
    }

    $seq = $pdo->query("SELECT pg_get_serial_sequence('case', 'number')")->fetchColumn();

    if ($seq) {
        $pdo->exec("SELECT setval('{$seq}', 1, false)");
        echo "OK secuencia número caso reiniciada\n";
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}

$app->getContainer()->getByClass(DataManager::class)->rebuild();

echo "\n=== Después ===\n";
echo 'Casos: ' . $counts('SELECT COUNT(*) FROM "case"') . "\n";
echo "Listo. Usuarios y contactos no se tocaron.\n";
