<?php

/**
 * Corrige casos cuya cFechaCaso quedó 5 h atrás (hora Bogotá guardada como UTC).
 *
 * Uso en Dokploy (contenedor espocrm):
 *   php /opt/bootstrap/repo/scripts/fix-cfecha-caso-timezone.php
 *   ESPO_CONFIRM_FIX=1 php /opt/bootstrap/repo/scripts/fix-cfecha-caso-timezone.php
 */

declare(strict_types=1);

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Custom\Tools\App\AlcaldiaDateTimeHelper;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$bogota = AlcaldiaDateTimeHelper::timeZone();
$utc = new \DateTimeZone('UTC');
$apply = trim((string) getenv('ESPO_CONFIRM_FIX')) === '1';

$cases = $em->getRDBRepository('Case')
    ->select(['id', 'name', 'cFechaCaso', 'createdAt'])
    ->where([
        'cFechaCaso!=' => null,
    ])
    ->find();

$toFix = [];

foreach ($cases as $case) {
    $createdAt = $case->get('createdAt');
    $rawFecha = $case->get('cFechaCaso');

    if (!$createdAt || !$rawFecha) {
        continue;
    }

    try {
        $created = \DateTimeImmutable::createFromInterface($createdAt)->setTimezone($bogota);
        $stored = new \DateTimeImmutable((string) $rawFecha, $utc);
        $fechaLocal = $stored->setTimezone($bogota);
    } catch (\Exception) {
        continue;
    }

    $diffSeconds = $created->getTimestamp() - $fechaLocal->getTimestamp();

    // Patrón del bug: cFechaCaso se ve ~5 h antes que createdAt en hora Bogotá.
    if ($diffSeconds >= 4 * 3600 && $diffSeconds <= 6 * 3600) {
        $corrected = $stored->modify('+5 hours');

        $toFix[] = [
            'id' => $case->getId(),
            'name' => (string) $case->get('name'),
            'before' => $stored->format('Y-m-d H:i:s') . ' UTC',
            'after' => $corrected->format('Y-m-d H:i:s') . ' UTC',
            'displayAfter' => $corrected->setTimezone($bogota)->format('d.m.Y H:i'),
        ];
    }
}

if ($toFix === []) {
    echo 'No hay casos con desfase de 5 h que corregir.' . PHP_EOL;
    exit(0);
}

echo ($apply ? 'Aplicando' : 'Simulación (sin cambios)') . ' — ' . count($toFix) . ' caso(s):' . PHP_EOL;

foreach ($toFix as $row) {
    echo "  {$row['id']} | {$row['name']}" . PHP_EOL;
    echo "    {$row['before']} -> {$row['after']} (pantalla: {$row['displayAfter']})" . PHP_EOL;

    if ($apply) {
        $entity = $em->getEntityById('Case', $row['id']);

        if ($entity) {
            $entity->set('cFechaCaso', substr($row['after'], 0, 19));
            $em->saveEntity($entity, ['skipHooks' => true]);
        }
    }
}

if (!$apply) {
    echo PHP_EOL . 'Para aplicar: ESPO_CONFIRM_FIX=1 php scripts/fix-cfecha-caso-timezone.php' . PHP_EOL;
}
