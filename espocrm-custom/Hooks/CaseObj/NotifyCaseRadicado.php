<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Field\LinkParent;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Name\Field;
use Espo\Entities\Email;
use Espo\Entities\Notification;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Core\Utils\Config;
use Exception;

/**
 * Cuando un caso pasa a estado Radicado, notifica (campana + correo)
 * a todos los usuarios activos con el rol Inspección.
 */
class NotifyCaseRadicado implements AfterSave
{
    public static int $order = 25;

    private const STATUS_RADICADO = 'Radicado';

    private const TYPE_RADICADO = Notification::TYPE_MESSAGE;

    /** Nombre del rol a notificar (crear en CRM y asignar a Juan). */
    private const ROLE_NOTIFY = 'Inspección';

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private EmailSender $emailSender,
        private Config $config
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isAttributeChanged('status')) {
            return;
        }

        if ($entity->get('status') !== self::STATUS_RADICADO) {
            return;
        }

        $numero = trim((string) ($entity->get('cNumeroRadicacion') ?? ''));

        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => self::ROLE_NOTIFY])
            ->findOne();

        if (!$role) {
            return;
        }

        $roleId = $role->getId();
        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                'isActive' => true,
                'type' => User::TYPE_REGULAR,
            ])
            ->find();

        $recordUrl = rtrim((string) $this->config->get('siteUrl'), '/')
            . '/#Case/view/' . $entity->getId();

        $numeroLabel = $numero !== '' ? $numero : 'sin número registrado';
        $expediente = trim((string) ($entity->get('cExpediente') ?? ''));

        foreach ($users as $notifyUser) {
            if ($notifyUser->getId() === $this->user->getId()) {
                continue;
            }

            $roles = $notifyUser->getLinkMultipleIdList('roles') ?? [];

            if (!in_array($roleId, $roles, true)) {
                continue;
            }

            $this->createNotification($entity, $notifyUser, $numeroLabel, $expediente);
            $this->sendEmail($entity, $notifyUser, $numeroLabel, $expediente, $recordUrl);
        }
    }

    private function createNotification(Entity $entity, User $notifyUser, string $numeroLabel, string $expediente): void
    {
        $entityName = (string) $entity->get(Field::NAME);
        $entityType = $entity->getEntityType();
        $entityId = $entity->getId();
        $caseHref = '#' . $entityType . '/view/' . $entityId;

        $messageHtml = htmlspecialchars($this->user->getName(), ENT_QUOTES, 'UTF-8')
            . ' radicó el caso <a href="' . $caseHref . '">'
            . htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8')
            . '</a> (N.º '
            . htmlspecialchars($numeroLabel, ENT_QUOTES, 'UTF-8')
            . ')'
            . ($expediente !== ''
                ? ' · Expediente: ' . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
                : '');

        $notification = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->getNew();

        $notification
            ->setType(self::TYPE_RADICADO)
            ->setUserId($notifyUser->getId())
            ->setMessage($messageHtml)
            ->setData([
                'entityType' => $entityType,
                'entityId' => $entityId,
                'entityName' => $entityName,
                'numeroRadicacion' => $numeroLabel,
                'expediente' => $expediente,
                'userId' => $this->user->getId(),
                'userName' => $this->user->getName(),
                'isRadicado' => true,
                'recordUrl' => $caseHref,
            ])
            ->setRelated(LinkParent::createFromEntity($entity));

        $this->entityManager->saveEntity($notification);
    }

    private function sendEmail(
        Entity $entity,
        User $notifyUser,
        string $numeroLabel,
        string $expediente,
        string $recordUrl
    ): void {
        if ($notifyUser->isPortal()) {
            return;
        }

        $emailAddress = $notifyUser->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        if (!$this->emailSender->hasSystemSmtp()) {
            return;
        }

        $body = '<p>' . htmlspecialchars($this->user->getName(), ENT_QUOTES, 'UTF-8')
            . ' radicó el caso <strong>' . htmlspecialchars($entity->get(Field::NAME), ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p><strong>Número de radicación:</strong> '
            . htmlspecialchars($numeroLabel, ENT_QUOTES, 'UTF-8') . '</p>'
            . ($expediente !== ''
                ? '<p><strong>Expediente:</strong> '
                    . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8') . '</p>'
                : '')
            . '<p><strong>Radicado por:</strong> '
            . htmlspecialchars($this->user->getName(), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><a href="' . htmlspecialchars($recordUrl, ENT_QUOTES, 'UTF-8') . '">Abrir caso en el CRM</a></p>';

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email->set([
            'subject' => 'Caso radicado – ' . $entity->get(Field::NAME),
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true,
            'parentId' => $entity->getId(),
            'parentType' => $entity->getEntityType(),
        ]);

        try {
            $this->emailSender->send($email);
        } catch (Exception) {
            // Sin SMTP configurado el aviso en campana sigue funcionando.
        }
    }
}
