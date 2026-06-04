<?php

namespace Espo\Custom\Classes\Select\Case\AccessControlFilters;

use Espo\Core\Select\AccessControl\Filter;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

/**
 * Portal + Asignador (Radicado) + Patrulleros (solo casos asignados a ellos en Radicado).
 */
class Mandatory implements Filter
{
    private const ROLE_ASIGNADOR = 'Asignador';
    private const TEAM_PATRULLEROS = 'Patrulleros';
    private const STATUS_RADICADO = 'Radicado';
    private const STATUS_EN_PROCESO = 'En proceso';

    private const BLOCKED_PATRULLERO_ROLES = [
        'Asignador',
        'Radicación',
        'Inspección',
        'Inspeccion',
    ];

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

        if ($this->isPatrulleroUser()) {
            $queryBuilder->where([
                'assignedUserId' => $this->user->getId(),
                'status' => self::STATUS_EN_PROCESO,
            ]);

            return;
        }

        if (!$this->hasAsignadorRole()) {
            return;
        }

        $queryBuilder->where(['status' => self::STATUS_RADICADO]);
    }

    private function isPatrulleroUser(): bool
    {
        if (!$this->isInPatrullerosTeam()) {
            return false;
        }

        foreach (self::BLOCKED_PATRULLERO_ROLES as $roleName) {
            if ($this->hasRoleByName($roleName)) {
                return false;
            }
        }

        return true;
    }

    private function isInPatrullerosTeam(): bool
    {
        $team = $this->entityManager
            ->getRDBRepositoryByClass(Team::class)
            ->where(['name' => self::TEAM_PATRULLEROS])
            ->findOne();

        if (!$team) {
            return false;
        }

        $teams = $this->user->getLinkMultipleIdList('teams') ?? [];

        return in_array($team->getId(), $teams, true);
    }

    private function hasRoleByName(string $roleName): bool
    {
        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => $roleName])
            ->findOne();

        if (!$role) {
            return false;
        }

        $roles = $this->user->getLinkMultipleIdList('roles') ?? [];

        return in_array($role->getId(), $roles, true);
    }

    private function hasAsignadorRole(): bool
    {
        return $this->hasRoleByName(self::ROLE_ASIGNADOR);
    }
}
