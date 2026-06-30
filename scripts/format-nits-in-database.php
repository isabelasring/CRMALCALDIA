<?php

/**
 * Formatea NIT existentes en BD al formato 900.123.456-7.
 *
 * Uso en Dokploy (contenedor espocrm):
 *   php /opt/bootstrap/repo/scripts/format-nits-in-database.php
 *   ESPO_CONFIRM_FORMAT=1 php /opt/bootstrap/repo/scripts/format-nits-in-database.php
 */

declare(strict_types=1);

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Custom\Tools\Party\DocumentNormalizer;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$apply = trim((string) getenv('ESPO_CONFIRM_FORMAT')) === '1';

$targets = [
    ['entity' => 'Account', 'field' => 'cNit'],
    ['entity' => 'Case', 'field' => 'cDocumentoPeticionario', 'tipo' => 'cTipoPersonaPeticionario'],
    ['entity' => 'Case', 'field' => 'cDocumentoPerjudicante', 'tipo' => 'cTipoPersonaPerjudicante'],
];

$updated = 0;

foreach ($targets as $target) {
    $entities = $em->getRDBRepository($target['entity'])->find();

    foreach ($entities as $entity) {
        if (isset($target['tipo']) && trim((string) $entity->get($target['tipo'])) !== 'Persona jurídica') {
            continue;
        }

        $raw = trim((string) $entity->get($target['field']));

        if ($raw === '') {
            continue;
        }

        $formatted = DocumentNormalizer::formatNit($raw);

        if ($formatted === $raw) {
            continue;
        }

        echo "{$target['entity']}.{$target['field']} {$entity->getId()}: {$raw} -> {$formatted}" . PHP_EOL;

        if ($apply) {
            $entity->set($target['field'], $formatted);
            $em->saveEntity($entity, ['skipHooks' => true]);
        }

        $updated++;
    }
}

if ($updated === 0) {
    echo 'No hay NIT por formatear.' . PHP_EOL;
    exit(0);
}

if (!$apply) {
    echo PHP_EOL . 'Simulación. Para aplicar: ESPO_CONFIRM_FORMAT=1 php scripts/format-nits-in-database.php' . PHP_EOL;
} else {
    echo PHP_EOL . "Actualizados: {$updated}" . PHP_EOL;
}
