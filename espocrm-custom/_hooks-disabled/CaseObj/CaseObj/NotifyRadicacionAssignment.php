<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Field\LinkParent;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\Name\Field;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Tools\EmailNotification\AssignmentProcessor;
use Espo\Tools\EmailNotification\AssignmentProcessorData;

class NotifyRadicacionAssignment implements AfterSave
{
    public static int $order = 20;

    private const STATUS_PENDIENTE = 'Pendiente de radicacion';

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private AssignmentProcessor $assignmentProcessor
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $assignedUserId = $entity->get('assignedUserId');

        if (!$assignedUserId || $entity->get('status') !== self::STATUS_PENDIENTE) {
            return;
        }

        $createdAt = $entity->get('createdAt');
        $modifiedAt = $entity->get('modifiedAt');
        $isJustCreated = $createdAt && $modifiedAt && $createdAt === $modifiedAt;

        if (!$isJustCreated && !$entity->isAttributeChanged('assignedUserId')) {
            return;
        }

        if ($assignedUserId === $this->user->getId()) {
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
                'isNew' => $isJustCreated,
                'userId' => $this->user->getId(),
                'userName' => $this->user->getName(),
            ])
            ->setRelated(LinkParent::createFromEntity($entity));

        $this->entityManager->saveEntity($notification);

        $this->assignmentProcessor->process(
            AssignmentProcessorData::create()
                ->withUserId($assignedUserId)
                ->withAssignerUserId($this->user->getId())
                ->withEntityId($entity->getId())
                ->withEntityType($entity->getEntityType())
        );
    }
}
