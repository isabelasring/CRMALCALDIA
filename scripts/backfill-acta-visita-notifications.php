<?php

/**
 * Corrige notificaciones de visita: tipo Message + isActaVisita (enlace vía JS).
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\Notification;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$fixed = 0;

foreach ($em->getRDBRepository('Notification')->find() as $notification) {
    $message = (string) $notification->get('message');

    if (!preg_match('/realizó la visita|se ha realizado la visita/i', $message)) {
        continue;
    }

    $data = $notification->get('data');

    if ($data instanceof stdClass) {
        $data = json_decode(json_encode($data), true);
    }

    if (!is_array($data)) {
        $data = [];
    }

    $entityType = $data['entityType'] ?? $notification->get('relatedType') ?? 'Case';
    $entityId = $data['entityId'] ?? $notification->get('relatedId');
    $entityName = $data['entityName'] ?? '';

    if (!$entityId) {
        continue;
    }

    $expediente = $data['expediente'] ?? '';

    if ($expediente === '' && preg_match('/expediente\s+([^)»«]+)/iu', $message, $m)) {
        $expediente = trim(strip_tags($m[1]));
    }

    $userName = (string) ($data['userName'] ?? 'Patrullero');

    $messagePlain = $userName
        . ' realizó la visita en el caso '
        . $entityName
        . ' (expediente '
        . ($expediente !== '' ? $expediente : 'sin expediente')
        . '). Revise el acta de visita.';

    $data['isActaVisita'] = true;
    $data['recordUrl'] = '#' . $entityType . '/view/' . $entityId;
    $data['expediente'] = $expediente;
    $data['entityType'] = $entityType;
    $data['entityId'] = $entityId;
    $data['entityName'] = $entityName;

    $notification->set('type', Notification::TYPE_MESSAGE);
    $notification->set('message', $messagePlain);
    $notification->set('data', $data);
    $notification->set('read', false);

    $em->saveEntity($notification);
    $fixed++;
}

echo "Notificaciones corregidas (tipo Message + enlace JS): {$fixed}\n";
echo "Cierra sesión, Ctrl+Shift+R y abre la campana.\n";
