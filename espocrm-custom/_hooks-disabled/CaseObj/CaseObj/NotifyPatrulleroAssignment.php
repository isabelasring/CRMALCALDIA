<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Field\LinkParent;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\Name\Field;
use Espo\Entities\Notification;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Cuando Julian asigna un caso Radicado a un patrullero (equipo Patrulleros),
 * solo notificación en campana (sin correo).
 */
class NotifyPatrulleroAssignment implements AfterSave
{
    public static int $order = 30;

    private const STATUS_RADICADO = 'Radicado';
    private const TEAM_PATRULLEROS = 'Patrulleros';

    public function __construct(
        private EntityManager $entityManager,
        private User $user
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isAttributeChanged('assignedUserId')) {
            return;
        }

        $previousStatus = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : null;

        if ($previousStatus !== self::STATUS_RADICADO && $entity->get('status') !== self::STATUS_RADICADO) {
            return;
        }

        $assignedUserId = $entity->get('assignedUserId');

        if (!$assignedUserId || $assignedUserId === $this->user->getId()) {
            return;
        }

        $team = $this->entityManager
            ->getRDBRepositoryByClass(Team::class)
            ->where(['name' => self::TEAM_PATRULLEROS])
            ->findOne();

        if (!$team) {
            return;
        }

        $assignedUser = $this->entityManager->getEntityById(User::ENTITY_TYPE, $assignedUserId);

        if (!$assignedUser) {
            return;
        }

        $teams = $assignedUser->getLinkMultipleIdList('teams') ?? [];

        if (!in_array($team->getId(), $teams, true)) {
            return;
        }

        $notification = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->getNew();

        $notification
            ->setType(Notification::TYPE_ASSIGN)
            ->setUserId($assignedUserId)
            ->setData([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'entityName' => $entity->get(Field::NAME),
                'isNew' => false,
                'userId' => $this->user->getId(),
                'userName' => $this->user->getName(),
            ])
            ->setRelated(LinkParent::createFromEntity($entity));

        $this->entityManager->saveEntity($notification);
    }
}
