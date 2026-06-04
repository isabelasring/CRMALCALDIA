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
 * Cuando un caso queda Radicado con expediente, avisa al rol Asignador (Julian)
 * para que asigne patrulleros.
 */
class NotifyAsignadorOnRadicado implements AfterSave
{
    public static int $order = 26;

    private const STATUS_RADICADO = 'Radicado';
    /** Espo 9 solo renderiza tipos conocidos (Message, Assign, …); "Info" queda invisible en campana. */
    private const TYPE_RADICADO = Notification::TYPE_MESSAGE;
    private const ROLE_ASIGNADOR = 'Asignador';

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private EmailSender $emailSender,
        private Config $config
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if ($entity->get('status') !== self::STATUS_RADICADO) {
            return;
        }

        $expediente = trim((string) ($entity->get('cExpediente') ?? ''));

        if ($expediente === '') {
            return;
        }

        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => self::ROLE_ASIGNADOR])
            ->findOne();

        if (!$role) {
            return;
        }

        $roleId = $role->getId();
        $numero = trim((string) ($entity->get('cNumeroRadicacion') ?? ''));
        $numeroLabel = $numero !== '' ? $numero : 'sin número';
        $recordUrl = rtrim((string) $this->config->get('siteUrl'), '/')
            . '/#Case/view/' . $entity->getId();

        foreach ($this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where(['isActive' => true, 'type' => User::TYPE_REGULAR])
            ->find() as $notifyUser) {

            $roles = $notifyUser->getLinkMultipleIdList('roles') ?? [];

            if (!in_array($roleId, $roles, true)) {
                continue;
            }

            if ($this->hasAsignadorNotification($entity, $notifyUser)) {
                continue;
            }

            $this->notify($entity, $notifyUser, $numeroLabel, $expediente, $recordUrl);
        }
    }

    private function hasAsignadorNotification(Entity $entity, User $notifyUser): bool
    {
        foreach ($this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->where([
                'userId' => $notifyUser->getId(),
                'relatedId' => $entity->getId(),
                'relatedType' => $entity->getEntityType(),
                'type' => self::TYPE_RADICADO,
            ])
            ->find() as $notification) {

            $data = $notification->get('data');

            if (is_object($data)) {
                $data = json_decode(json_encode($data), true);
            }

            if (is_array($data) && !empty($data['isAsignador'])) {
                return true;
            }
        }

        return false;
    }

    private function notify(
        Entity $entity,
        User $notifyUser,
        string $numeroLabel,
        string $expediente,
        string $recordUrl
    ): void {
        $entityName = (string) $entity->get(Field::NAME);
        $entityType = $entity->getEntityType();
        $entityId = $entity->getId();
        $caseHref = '#' . $entityType . '/view/' . $entityId;

        $messagePlain = htmlspecialchars($this->user->getName(), ENT_QUOTES, 'UTF-8')
            . ' radicó un caso para asignar: '
            . htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8')
            . ' · Expediente '
            . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
            . ' (N.º '
            . htmlspecialchars($numeroLabel, ENT_QUOTES, 'UTF-8')
            . ')';

        $messageHtml = htmlspecialchars($this->user->getName(), ENT_QUOTES, 'UTF-8')
            . ' radicó un caso para asignar: <a href="' . $caseHref . '">'
            . htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8')
            . '</a> · Expediente '
            . htmlspecialchars($expediente, ENT_QUOTES, 'UTF-8')
            . ' (N.º '
            . htmlspecialchars($numeroLabel, ENT_QUOTES, 'UTF-8')
            . ')';

        $notification = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->getNew();

        $notification
            ->setType(self::TYPE_RADICADO)
            ->setUserId($notifyUser->getId())
            ->setMessage($messagePlain)
            ->setData([
                'entityType' => $entityType,
                'entityId' => $entityId,
                'entityName' => $entityName,
                'numeroRadicacion' => $numeroLabel,
                'expediente' => $expediente,
                'userId' => $this->user->getId(),
                'userName' => $this->user->getName(),
                'isRadicado' => true,
                'isAsignador' => true,
                'recordUrl' => $caseHref,
            ])
            ->setRelated(LinkParent::createFromEntity($entity));

        $this->entityManager->saveEntity($notification);

        $emailAddress = $notifyUser->get('emailAddress');

        if (!$emailAddress || !$this->emailSender->hasSystemSmtp()) {
            return;
        }

        $body = '<p>' . $messageHtml . '</p>'
            . '<p><a href="' . htmlspecialchars($recordUrl, ENT_QUOTES, 'UTF-8') . '">Abrir caso para asignar</a></p>';

        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);
        $email->set([
            'subject' => 'Caso para asignar – ' . $entityName,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true,
            'parentId' => $entityId,
            'parentType' => $entityType,
        ]);

        try {
            $this->emailSender->send($email);
        } catch (Exception) {
        }
    }
}
