<?php

/**
 * Notifica a Inspección (Juan) y Asignador (Julian) casos con radicado/expediente
 * guardados antes de activar el hook o sin aviso previo.
 *
 * docker cp scripts/backfill-radicado-notifications.php espocrm:/tmp/
 * docker exec espocrm php /tmp/backfill-radicado-notifications.php
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
use Exception;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);
$config = $app->getContainer()->getByClass(Config::class);
$emailSender = $app->getContainer()->getByClass(EmailSender::class);

$roleInspeccion = $em->getRDBRepositoryByClass(Role::class)->where(['name' => 'Inspección'])->findOne();
$roleAsignador = $em->getRDBRepositoryByClass(Role::class)->where(['name' => 'Asignador'])->findOne();

if (!$roleInspeccion || !$roleAsignador) {
    echo "Faltan roles Inspección o Asignador.\n";
    exit(1);
}

$sent = 0;

foreach ($em->getRDBRepository('Case')->find() as $case) {
    $numero = trim((string) $case->get('cNumeroRadicado'));
    $expediente = trim((string) $case->get('cExpediente'));

    if ($numero === '' && $expediente === '') {
        continue;
    }

    $modifiedById = $case->get('modifiedById');
    $radicador = $modifiedById
        ? $em->getEntityById(User::ENTITY_TYPE, $modifiedById)
        : null;
    $radicadorName = $radicador ? (string) $radicador->get('name') : 'Radicación';
    $radicadorId = $radicador?->getId();

    $numeroLabel = $numero !== '' ? $numero : 'sin número';
    $linkLabel = $numero !== '' ? $numero : ($expediente !== '' ? $expediente : 'Caso');
    $caseHref = '#Case/view/' . $case->getId();
    $recordUrl = rtrim((string) $config->get('siteUrl'), '/') . $caseHref;

    $targets = [
        ['roleId' => $roleInspeccion->getId(), 'forAsignador' => false],
        ['roleId' => $roleAsignador->getId(), 'forAsignador' => true],
    ];

    foreach ($targets as $target) {
        foreach (
            $em->getRDBRepositoryByClass(User::class)
                ->where(['isActive' => true, 'type' => User::TYPE_REGULAR])
                ->find() as $notifyUser
        ) {
            if ($radicadorId && $notifyUser->getId() === $radicadorId) {
                continue;
            }

            $roles = $notifyUser->getLinkMultipleIdList('roles') ?? [];

            if (!in_array($target['roleId'], $roles, true)) {
                continue;
            }

            if (hasNotification($em, $case, $notifyUser, $target['forAsignador'])) {
                continue;
            }

            createNotification(
                $em,
                $case,
                $notifyUser,
                $target['forAsignador'],
                $radicadorName,
                $radicadorId,
                $linkLabel,
                $numeroLabel,
                $expediente,
                $caseHref
            );

            sendEmail(
                $em,
                $emailSender,
                $case,
                $notifyUser,
                $target['forAsignador'],
                $radicadorName,
                $linkLabel,
                $numeroLabel,
                $expediente,
                $recordUrl
            );

            $sent++;
            echo "Notificación → {$notifyUser->get('userName')} | {$linkLabel} (exp. {$expediente})\n";
        }
    }
}

echo PHP_EOL . "Listo. Notificaciones creadas: {$sent}\n";

function hasNotification(
    EntityManager $em,
    $case,
    User $notifyUser,
    bool $forAsignador
): bool {
    foreach (
        $em->getRDBRepositoryByClass(Notification::class)
            ->where([
                'userId' => $notifyUser->getId(),
                'relatedId' => $case->getId(),
                'relatedType' => 'Case',
                'type' => Notification::TYPE_MESSAGE,
            ])
            ->find() as $notification
    ) {
        $data = $notification->get('data');

        if ($data instanceof \stdClass) {
            $data = json_decode(json_encode($data), true);
        }

        if (!is_array($data)) {
            continue;
        }

        if ($forAsignador && !empty($data['isAsignador'])) {
            return true;
        }

        if (!$forAsignador && !empty($data['isRadicado']) && empty($data['isAsignador'])) {
            return true;
        }
    }

    return false;
}

function createNotification(
    EntityManager $em,
    $case,
    User $notifyUser,
    bool $forAsignador,
    string $radicadorName,
    ?string $radicadorId,
    string $linkLabel,
    string $numeroLabel,
    string $expediente,
    string $caseHref
): void {
    if ($forAsignador) {
        $messageHtml = htmlspecialchars($radicadorName, ENT_QUOTES, 'UTF-8')
            . ' radicó un caso para asignar: <a href="' . $caseHref . '">'
            . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . '</a>'
            . ($expediente !== ''
                ? ' · Expediente ' . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
                : '');
    } else {
        $messageHtml = htmlspecialchars($radicadorName, ENT_QUOTES, 'UTF-8')
            . ' radicó el caso <a href="' . $caseHref . '">'
            . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . '</a>'
            . ($expediente !== ''
                ? ' · Expediente: ' . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
                : '');
    }

    $notification = $em->getRDBRepositoryByClass(Notification::class)->getNew();
    $notification
        ->setType(Notification::TYPE_MESSAGE)
        ->setUserId($notifyUser->getId())
        ->setMessage($messageHtml)
        ->setData([
            'entityType' => 'Case',
            'entityId' => $case->getId(),
            'entityName' => $linkLabel,
            'numeroRadicacion' => $numeroLabel,
            'expediente' => $expediente,
            'userId' => $radicadorId,
            'userName' => $radicadorName,
            'isRadicado' => true,
            'isAsignador' => $forAsignador,
            'recordUrl' => $caseHref,
        ])
        ->setRelated(LinkParent::createFromEntity($case));

    $em->saveEntity($notification);
}

function sendEmail(
    EntityManager $em,
    EmailSender $emailSender,
    $case,
    User $notifyUser,
    bool $forAsignador,
    string $radicadorName,
    string $linkLabel,
    string $numeroLabel,
    string $expediente,
    string $recordUrl
): void {
    if ($notifyUser->isPortal()) {
        return;
    }

    $emailAddress = $notifyUser->get('emailAddress');

    if (!$emailAddress || !$emailSender->hasSystemSmtp()) {
        return;
    }

    $subject = $forAsignador
        ? 'Caso para asignar – ' . $linkLabel
        : 'Caso radicado – ' . $linkLabel;

    $body = '<p>' . htmlspecialchars($radicadorName, ENT_QUOTES, 'UTF-8');

    if ($forAsignador) {
        $body .= ' radicó un caso para asignar: <strong>'
            . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . '</strong></p>';
    } else {
        $body .= ' radicó el caso <strong>'
            . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . '</strong></p>';
    }

    $body .= '<p><strong>N.º radicado:</strong> '
        . htmlspecialchars($numeroLabel, ENT_QUOTES, 'UTF-8') . '</p>';

    if ($expediente !== '') {
        $body .= '<p><strong>Expediente:</strong> '
            . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $body .= '<p><a href="' . htmlspecialchars($recordUrl, ENT_QUOTES, 'UTF-8')
        . '">Abrir caso en el CRM</a></p>';

    /** @var Email $email */
    $email = $em->getNewEntity(Email::ENTITY_TYPE);
    $email->set([
        'subject' => $subject,
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
