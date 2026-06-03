<?php

namespace Espo\Custom\Classes\Select\Case\AccessControlFilters;

use Espo\Core\Select\AccessControl\Filter;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

/**
 * Portal + usuarios con rol Asignador solo ven casos en estado Radicado.
 */
class Mandatory implements Filter
{
    private const ROLE_ASIGNADOR = 'Asignador';
    private const STATUS_RADICADO = 'Radicado';

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function apply(QueryBuilder $queryBuilder): void
    {
        if ($this->user->isPortal()) {
            $queryBuilder->where(['isInternal' => false]);
        }

        if ($this->user->isAdmin()) {
            return;
        }

        if (!$this->hasAsignadorRole()) {
            return;
        }

        $queryBuilder->where(['status' => self::STATUS_RADICADO]);
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
