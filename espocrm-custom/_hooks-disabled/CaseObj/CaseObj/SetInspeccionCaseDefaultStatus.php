<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Usuarios Inspección (Juan): no eligen estado; se asigna al crear.
 */
class SetInspeccionCaseDefaultStatus implements BeforeSave
{
    public static int $order = 5;

    private const ROLE_INSPECCION = 'Inspección';
    private const DEFAULT_STATUS = 'Pendiente de radicacion';

    public function __construct(
        private EntityManager $entityManager,
        private User $user
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew() || !$this->hasInspeccionRole()) {
            return;
        }

        $entity->set('status', self::DEFAULT_STATUS);
    }

    private function hasInspeccionRole(): bool
    {
        if ($this->user->isAdmin() || $this->user->isPortal()) {
            return false;
        }

        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => self::ROLE_INSPECCION])
            ->findOne();

        if (!$role) {
            return false;
        }

        $roles = $this->user->getLinkMultipleIdList('roles') ?? [];

        return in_array($role->getId(), $roles, true);
    }
}
