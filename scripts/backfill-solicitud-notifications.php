<?php

/**
 * Notifica a Radicación (Edwin) casos ya creados por Inspección sin aviso previo.
 *
 * docker cp scripts/backfill-solicitud-notifications.php espocrm:/tmp/
 * docker exec espocrm php /tmp/backfill-solicitud-notifications.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Field\LinkParent;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Utils\Config;
use Espo\Entities\Email;
use Espo\Entities\Notification;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$config = $app->getContainer()->getByClass(Config::class);
$emailSender = $app->getContainer()->getByClass(EmailSender::class);

$roleInspeccion = $em->getRDBRepositoryByClass(Role::class)
    ->where(['name' => 'Inspección'])
    ->findOne();
$roleRadicacion = $em->getRDBRepositoryByClass(Role::class)
    ->where(['name' => 'Radicación'])
    ->findOne();

if (!$roleInspeccion || !$roleRadicacion) {
    echo "Faltan roles Inspección o Radicación.\n";
    exit(1);
}

$inspeccionUserIds = [];

foreach (
    $em->getRDBRepositoryByClass(User::class)
        ->where(['isActive' => true, 'type' => User::TYPE_REGULAR])
        ->find() as $user
) {
    $roles = $user->getLinkMultipleIdList('roles') ?? [];

    if (in_array($roleInspeccion->getId(), $roles, true)) {
        $inspeccionUserIds[] = $user->getId();
    }
}

if ($inspeccionUserIds === []) {
    echo "No hay usuarios con rol Inspección.\n";
    exit(0);
}

$radicacionUserIds = [];

foreach (
    $em->getRDBRepositoryByClass(User::class)
        ->where(['isActive' => true, 'type' => User::TYPE_REGULAR])
        ->find() as $user
) {
    $roles = $user->getLinkMultipleIdList('roles') ?? [];

    if (in_array($roleRadicacion->getId(), $roles, true)) {
        $radicacionUserIds[] = $user->getId();
    }
}

$sent = 0;

foreach ($em->getRDBRepository('Case')->find() as $case) {
    $createdById = $case->get('createdById');

    if (!$createdById || !in_array($createdById, $inspeccionUserIds, true)) {
        continue;
    }

    $creator = $em->getEntityById(User::ENTITY_TYPE, $createdById);
    $creatorName = $creator ? (string) $creator->get('name') : 'Inspección';

    $label = trim((string) $case->get('cPeticionario'));
    if ($label === '') {
        $label = trim((string) $case->get('name')) ?: 'Solicitud de queja';
    }

    $notifyIds = array_values(array_unique(array_filter(array_merge(
        $case->get('cRemitidoAId') ? [(string) $case->get('cRemitidoAId')] : [],
        $radicacionUserIds
    ))));

    $caseHref = '#Case/view/' . $case->getId();
    $recordUrl = rtrim((string) $config->get('siteUrl'), '/') . $caseHref;

    foreach ($notifyIds as $notifyUserId) {
        if ($notifyUserId === $createdById) {
            continue;
        }

        $exists = $em->getRDBRepositoryByClass(Notification::class)
            ->where([
                'userId' => $notifyUserId,
                'relatedId' => $case->getId(),
                'relatedType' => 'Case',
            ])
            ->findOne();

        if ($exists) {
            continue;
        }

        $notifyUser = $em->getEntityById(User::ENTITY_TYPE, $notifyUserId);

        if (!$notifyUser || !$notifyUser->get('isActive')) {
            continue;
        }

        $messageHtml = htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8')
            . ' creó una solicitud de queja: <a href="' . $caseHref . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';

        $notification = $em->getRDBRepositoryByClass(Notification::class)->getNew();
        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setUserId($notifyUserId)
            ->setMessage($messageHtml)
            ->setData([
                'entityType' => 'Case',
                'entityId' => $case->getId(),
                'entityName' => $label,
                'userId' => $createdById,
                'userName' => $creatorName,
                'isNuevaSolicitud' => true,
                'recordUrl' => $caseHref,
            ])
            ->setRelated(LinkParent::createFromEntity($case));

        $em->saveEntity($notification);
        $sent++;

        echo "Notificación → {$notifyUser->get('userName')} | caso {$label} ({$case->getId()})\n";

        $emailAddress = $notifyUser->get('emailAddress');

        if ($emailAddress && $emailSender->hasSystemSmtp()) {
            $body = '<p>' . htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8')
                . ' creó una solicitud de queja: <strong>'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                . '<p><a href="' . htmlspecialchars($recordUrl, ENT_QUOTES, 'UTF-8')
                . '">Abrir caso en el CRM</a></p>';

            /** @var Email $email */
            $email = $em->getNewEntity(Email::ENTITY_TYPE);
            $email->set([
                'subject' => 'Nueva solicitud de queja – ' . $label,
                'body' => $body,
                'isHtml' => true,
                'to' => $emailAddress,
                'isSystem' => true,
                'parentId' => $case->getId(),
                'parentType' => 'Case',
            ]);

            try {
                $emailSender->send($email);
            } catch (Exception) {
                // Campana basta.
            }
        }
    }
}

echo PHP_EOL . "Listo. Notificaciones creadas: {$sent}\n";
