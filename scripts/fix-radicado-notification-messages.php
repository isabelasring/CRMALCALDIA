<?php

/**
 * Corrige texto de notificaciones de radicado: enlace = número de radicado.
 *
 * docker cp scripts/fix-radicado-notification-messages.php espocrm:/tmp/
 * docker exec espocrm php /tmp/fix-radicado-notification-messages.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\Notification;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$updated = 0;

foreach ($em->getRDBRepositoryByClass(Notification::class)->find() as $notification) {
    $data = $notification->get('data');

    if ($data instanceof \stdClass) {
        $data = json_decode(json_encode($data), true);
    }

    if (!is_array($data) || empty($data['isRadicado'])) {
        continue;
    }

    $caseId = $data['entityId'] ?? $notification->get('relatedId');

    if (!$caseId) {
        continue;
    }

    $case = $em->getEntityById('Case', $caseId);

    if (!$case) {
        continue;
    }

    $numero = trim((string) $case->get('cNumeroRadicado'));
    $expediente = trim((string) $case->get('cExpediente'));
    $numeroLabel = $numero !== '' ? $numero : 'sin número';
    $linkLabel = $numero !== '' ? $numero : ($expediente !== '' ? $expediente : 'Caso');
    $caseHref = '#Case/view/' . $case->getId();
    $userName = (string) ($data['userName'] ?? 'Radicación');
    $forAsignador = !empty($data['isAsignador']);

    if ($forAsignador) {
        $messageHtml = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8')
            . ' radicó un caso para asignar: <a href="' . $caseHref . '">'
            . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . '</a>'
            . ($expediente !== ''
                ? ' · Expediente ' . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
                : '');
    } else {
        $messageHtml = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8')
            . ' radicó el caso <a href="' . $caseHref . '">'
            . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . '</a>'
            . ($expediente !== ''
                ? ' · Expediente: ' . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
                : '');
    }

    $data['entityName'] = $linkLabel;
    $data['numeroRadicacion'] = $numeroLabel;
    $data['expediente'] = $expediente;
    $data['recordUrl'] = $caseHref;

    $notification->set('message', $messageHtml);
    $notification->set('data', $data);
    $em->saveEntity($notification, ['skipHooks' => true]);

    $updated++;
    echo "Actualizada notificación {$notification->getId()} → enlace: {$linkLabel}\n";
}

echo PHP_EOL . "Listo. Notificaciones corregidas: {$updated}\n";
