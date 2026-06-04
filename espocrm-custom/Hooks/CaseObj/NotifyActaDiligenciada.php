<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Field\LinkParent;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\Name\Field;
use Espo\Entities\Notification;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Acta diligenciada → notifica a Inspección (Juan) y Asignador (Julian).
 */
class NotifyActaDiligenciada implements AfterSave
{
    public static int $order = 35;

    private const STATUS_VISITA_REALIZADA = 'Visita realizada';
    private const ACTA_DILIGENCIADA = 'Diligenciada';

    private const ROLES_NOTIFY = ['Inspección', 'Asignador'];

    public function __construct(
        private EntityManager $entityManager,
        private User $user
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if ($entity->get('status') !== self::STATUS_VISITA_REALIZADA) {
            return;
        }

        if ($entity->get('cActaEstado') !== self::ACTA_DILIGENCIADA) {
            return;
        }

        if ($entity->get('cActaRegistroOficial')) {
            return;
        }

        $statusChanged = $entity->isAttributeChanged('status');
        $actaChanged = $entity->isAttributeChanged('cActaEstado');

        if (!$statusChanged && !$actaChanged) {
            return;
        }

        foreach (self::ROLES_NOTIFY as $roleName) {
            $this->notifyRole($entity, $roleName);
        }
    }

    private function notifyRole(Entity $entity, string $roleName): void
    {
        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => $roleName])
            ->findOne();

        if (!$role) {
            return;
        }

        $roleId = $role->getId();

        foreach ($this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where(['isActive' => true, 'type' => User::TYPE_REGULAR])
            ->find() as $notifyUser) {

            if ($notifyUser->getId() === $this->user->getId()) {
                continue;
            }

            $roles = $notifyUser->getLinkMultipleIdList('roles') ?? [];

            if (!in_array($roleId, $roles, true)) {
                continue;
            }

            if ($this->hasNotification($entity, $notifyUser)) {
                continue;
            }

            $entityName = (string) $entity->get(Field::NAME);
            $entityType = $entity->getEntityType();
            $entityId = $entity->getId();
            $caseHref = '#' . $entityType . '/view/' . $entityId;
            $expediente = trim((string) ($entity->get('cExpediente') ?? ''));
            $expedienteLabel = $expediente !== '' ? $expediente : 'sin expediente';
            $patrulleroName = htmlspecialchars($this->user->getName(), ENT_QUOTES, 'UTF-8');
            $escapedName = htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8');
            $escapedExp = htmlspecialchars($expedienteLabel, ENT_QUOTES, 'UTF-8');

            $messagePlain = $patrulleroName
                . ' realizó la visita en el caso '
                . $entityName
                . ' (expediente '
                . $expedienteLabel
                . '). Revise el acta de visita.';

            $notification = $this->entityManager
                ->getRDBRepositoryByClass(Notification::class)
                ->getNew();

            $notification
                ->setType(Notification::TYPE_MESSAGE)
                ->setUserId($notifyUser->getId())
                ->setMessage($messagePlain)
                ->setData([
                    'entityType' => $entityType,
                    'entityId' => $entityId,
                    'entityName' => $entityName,
                    'expediente' => $expedienteLabel,
                    'userId' => $this->user->getId(),
                    'userName' => $this->user->getName(),
                    'isActaVisita' => true,
                    'recordUrl' => $caseHref,
                ])
                ->setRelated(LinkParent::createFromEntity($entity));

            $this->entityManager->saveEntity($notification);
        }
    }

    private function hasNotification(Entity $entity, User $notifyUser): bool
    {
        $existing = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->where([
                'userId' => $notifyUser->getId(),
                'relatedId' => $entity->getId(),
                'relatedType' => $entity->getEntityType(),
                'type' => Notification::TYPE_MESSAGE,
            ])
            ->order('createdAt', 'DESC')
            ->findOne();

        if ($existing && str_contains((string) $existing->get('message'), 'se ha realizado la visita')) {
            return true;
        }

        return false;
    }
}
