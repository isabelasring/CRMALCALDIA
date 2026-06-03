<?php

/**
 * Crea notificaciones y correos de radicación para casos ya en estado Radicado.
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Field\LinkParent;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Name\Field;
use Espo\Entities\Email;
use Espo\Entities\Notification;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Exception;

$app = new Application();
$app->setupSystemUser();

$container = $app->getContainer();
/** @var EntityManager $em */
$em = $container->getByClass(EntityManager::class);
$config = $container->getByClass(Config::class);
$emailSender = $container->getByClass(EmailSender::class);

$role = $em->getRDBRepositoryByClass(Role::class)->where(['name' => 'Inspección'])->findOne();

if (!$role) {
    echo "Rol Inspección no existe.\n";
    exit(1);
}

$roleId = $role->getId();
$admin = $em->getRDBRepositoryByClass(User::class)->where(['userName' => 'edwin.radicacion'])->findOne()
    ?: $em->getRDBRepositoryByClass(User::class)->where(['type' => 'admin'])->findOne();

$assignerId = $admin?->getId();
$assignerName = $admin?->get('name') ?: 'Sistema';

$sent = 0;

foreach ($em->getRDBRepository('Case')->where(['status' => 'Radicado'])->find() as $case) {
    $numero = trim((string) ($case->get('cNumeroRadicacion') ?? ''));
    $numeroLabel = $numero !== '' ? $numero : 'sin número registrado';
    $recordUrl = rtrim((string) $config->get('siteUrl'), '/') . '/#Case/view/' . $case->getId();
    $caseHref = '#Case/view/' . $case->getId();
    $entityName = (string) $case->get(Field::NAME);

    foreach ($em->getRDBRepositoryByClass(User::class)->where(['isActive' => true, 'type' => 'regular'])->find() as $user) {
        $roles = $user->getLinkMultipleIdList('roles') ?? [];
        if (!in_array($roleId, $roles, true)) {
            continue;
        }

        foreach ($em->getRDBRepositoryByClass(Notification::class)
            ->where([
                'userId' => $user->getId(),
                'relatedId' => $case->getId(),
                'relatedType' => 'Case',
            ])
            ->find() as $old) {
            $em->removeEntity($old);
        }

        $messageHtml = htmlspecialchars($assignerName, ENT_QUOTES, 'UTF-8')
            . ' radicó el caso <a href="' . $caseHref . '">'
            . htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8')
            . '</a> (N.º '
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
                'userId' => $assignerId,
                'userName' => $assignerName,
                'isRadicado' => true,
                'recordUrl' => $caseHref,
            ])
            ->setRelated(LinkParent::createFromEntity($case));
        $em->saveEntity($notification);

        $emailAddress = $user->get('emailAddress');
        if ($emailAddress && $emailSender->hasSystemSmtp()) {
            $body = '<p>' . $messageHtml . '</p>'
                . '<p><a href="' . htmlspecialchars($recordUrl, ENT_QUOTES, 'UTF-8') . '">Abrir caso en el CRM</a></p>';
            $email = $em->getNewEntity(Email::ENTITY_TYPE);
            $email->set([
                'subject' => 'Caso radicado – ' . $case->get(Field::NAME),
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

echo PHP_EOL . "Procesados: {$sent}" . PHP_EOL;
