<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Rol Asignador: al guardar solo puede cambiar assignedUserId.
 */
class LimitAsignadorCaseEdit implements BeforeSave
{
    private const ROLE_ASIGNADOR = 'Asignador';

    private const ALLOWED = ['assignedUserId', 'assignedUserName', 'status'];

    private const STATUS_RADICADO = 'Radicado';
    private const STATUS_EN_PROCESO = 'En proceso';

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || $entity->isNew() || !$this->hasAsignadorRole()) {
            return;
        }

        $statusForEdit = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : $entity->get('status');

        if ($statusForEdit !== self::STATUS_RADICADO) {
            return;
        }

        foreach ($entity->getAttributeList() as $attribute) {
            if (!$entity->isAttributeChanged($attribute)) {
                continue;
            }

            if ($attribute === 'status' && $this->isAllowedStatusTransition($entity)) {
                continue;
            }

            if (in_array($attribute, self::ALLOWED, true)) {
                continue;
            }

            if ($entity->hasFetched($attribute)) {
                $entity->set($attribute, $entity->getFetched($attribute));
            }
        }
    }

    private function isAllowedStatusTransition(Entity $entity): bool
    {
        if (!$entity->isAttributeChanged('assignedUserId')) {
            return false;
        }

        $previousStatus = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : null;

        return $previousStatus === self::STATUS_RADICADO
            && $entity->get('status') === self::STATUS_EN_PROCESO;
    }

    private function hasAsignadorRole(): bool
    {
        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => self::ROLE_ASIGNADOR])
            ->findOne();

        if (!$role) {
            return false;
        }

        $roles = $this->user->getLinkMultipleIdList('roles') ?? [];

        return in_array($role->getId(), $roles, true);
    }
}
