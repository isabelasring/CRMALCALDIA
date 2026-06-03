<?php

/**
 * Corrige notificaciones con tipo inválido (Info, Radicado) → Message.
 * Espo 9 no renderiza tipos desconocidos en la campana.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\Notification;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$fixed = 0;
$unread = 0;

foreach ($em->getRDBRepository('Notification')->find() as $n) {
    $type = (string) $n->get('type');
    if (!in_array($type, ['Info', 'Radicado'], true)) {
        continue;
    }
    $n->set('type', Notification::TYPE_MESSAGE);
    $n->set('read', false);
    $em->saveEntity($n);
    $fixed++;
    $unread++;
}

echo "Notificaciones corregidas (Info/Radicado → Message): $fixed\n";
echo "Marcadas no leídas: $unread\n";
echo "Julian: cierra sesión, Ctrl+Shift+R y abre la campana.\n";
