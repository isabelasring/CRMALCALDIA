<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Rol Asignador: no crear casos; solo editar casos en estado Radicado.
 */
class RestrictAsignadorCaseAccess implements BeforeSave
{
    private const ROLE_ASIGNADOR = 'Asignador';
    private const STATUS_RADICADO = 'Radicado';

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || !$this->hasAsignadorRole()) {
            return;
        }

        if ($entity->isNew()) {
            throw Forbidden::create('Los asignadores no pueden crear casos.');
        }

        $statusForEdit = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : $entity->get('status');

        if ($statusForEdit !== self::STATUS_RADICADO) {
            throw Forbidden::create('Solo puede gestionar casos en estado Radicado.');
        }
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
