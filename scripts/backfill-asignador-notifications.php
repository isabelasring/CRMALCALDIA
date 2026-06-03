<?php

/**
 * Notificaciones para rol Asignador en casos ya Radicados (con expediente).
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Field\LinkParent;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Entities\Email;
use Espo\Entities\Notification;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);
$config = $app->getContainer()->getByClass(Config::class);
$emailSender = $app->getContainer()->getByClass(EmailSender::class);

$role = $em->getRDBRepositoryByClass(Role::class)->where(['name' => 'Asignador'])->findOne();

if (!$role) {
    echo "Rol Asignador no existe.\n";
    exit(1);
}

$roleId = $role->getId();
$admin = $em->getRDBRepositoryByClass(User::class)->where(['userName' => 'edwin.radicacion'])->findOne()
    ?: $em->getRDBRepositoryByClass(User::class)->where(['type' => 'admin'])->findOne();

$assignerName = $admin?->get('name') ?: 'Sistema';
$assignerId = $admin?->getId();
$sent = 0;

foreach ($em->getRDBRepository('Case')->where(['status' => 'Radicado'])->find() as $case) {
    $expediente = trim((string) ($case->get('cExpediente') ?? ''));

    if ($expediente === '') {
        continue;
    }

    $numero = trim((string) ($case->get('cNumeroRadicacion') ?? ''));
    $numeroLabel = $numero !== '' ? $numero : 'sin número';
    $caseHref = '#Case/view/' . $case->getId();
    $recordUrl = rtrim((string) $config->get('siteUrl'), '/') . '/#Case/view/' . $case->getId();
    $entityName = (string) $case->get(Field::NAME);

    foreach ($em->getRDBRepositoryByClass(User::class)->where(['isActive' => true, 'type' => 'regular'])->find() as $user) {
        $roles = $user->getLinkMultipleIdList('roles') ?? [];

        if (!in_array($roleId, $roles, true)) {
            continue;
        }

        $exists = false;

        foreach ($em->getRDBRepositoryByClass(Notification::class)
            ->where([
                'userId' => $user->getId(),
                'relatedId' => $case->getId(),
                'relatedType' => 'Case',
                'type' => Notification::TYPE_MESSAGE,
            ])
            ->find() as $old) {
            $data = $old->get('data');

            if (is_object($data)) {
                $data = json_decode(json_encode($data), true);
            }

            if (is_array($data) && !empty($data['isAsignador'])) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            echo 'Ya existe: ' . $user->get('userName') . ' - ' . $case->get('name') . PHP_EOL;
            continue;
        }

        $messageHtml = htmlspecialchars($assignerName, ENT_QUOTES, 'UTF-8')
            . ' radicó un caso para asignar: <a href="' . $caseHref . '">'
            . htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8')
            . '</a> · Expediente '
            . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
            . ' (N.º '
            . htmlspecialchars($numeroLabel, ENT_QUOTES, 'UTF-8')
            . ')';

        $notification = $em->getRDBRepositoryByClass(Notification::class)->getNew();
        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setUserId($user->getId())
            ->setMessage($messageHtml)
            ->setData([
                'entityType' => 'Case',
                'entityId' => $case->getId(),
                'entityName' => $entityName,
                'numeroRadicacion' => $numeroLabel,
                'expediente' => $expediente,
                'userId' => $assignerId,
                'userName' => $assignerName,
                'isRadicado' => true,
                'isAsignador' => true,
                'recordUrl' => $caseHref,
            ])
            ->setRelated(LinkParent::createFromEntity($case));
        $em->saveEntity($notification);

        $emailAddress = $user->get('emailAddress');

        if ($emailAddress && $emailSender->hasSystemSmtp()) {
            $body = '<p>' . $messageHtml . '</p>'
                . '<p><a href="' . htmlspecialchars($recordUrl, ENT_QUOTES, 'UTF-8') . '">Abrir caso para asignar</a></p>';
            $email = $em->getNewEntity(Email::ENTITY_TYPE);
            $email->set([
                'subject' => 'Caso para asignar – ' . $entityName,
                'body' => $body,
                'isHtml' => true,
                'to' => $emailAddress,
                'isSystem' => true,
                'parentId' => $case->getId(),
                'parentType' => 'Case',
            ]);

            try {
                $emailSender->send($email);
                echo 'Email OK: ' . $user->get('userName') . PHP_EOL;
            } catch (Exception $e) {
                echo 'Email error: ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo 'Notificación: ' . $user->get('userName') . ' - ' . $case->get('name') . PHP_EOL;
        $sent++;
    }
}

echo PHP_EOL . "Creadas: {$sent}" . PHP_EOL;
