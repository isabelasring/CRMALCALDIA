<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Custom\Tools\User\AlcaldiaUserProfile;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Rol Asignador (sin Inspección/Radicación): no puede crear casos nuevos.
 */
class RestrictAsignadorCaseCreate implements BeforeSave
{
    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || !$entity->isNew()) {
            return;
        }

        $profile = new AlcaldiaUserProfile($this->entityManager);

        if (!$profile->isAsignador($this->user) || $profile->isInspeccion($this->user)) {
            return;
        }

        throw new Forbidden('El rol de asignación no puede crear casos nuevos.');
    }
}
