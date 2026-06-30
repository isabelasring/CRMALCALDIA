<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Custom\Tools\User\AlcaldiaUserProfile;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Custom\Tools\User\AlcaldiaRolesConfig;

/**
 * Rol Patrullero: solo consulta casos asignados, sin editar el caso.
 * El acta de visita se diligencia en la entidad ActaVisita.
 */
class LimitPatrulleroCaseEdit implements BeforeSave
{
    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (AlcaldiaRolesConfig::isDisabled()) {
            return;
        }

        if ($this->user->isAdmin()) {
            return;
        }

        $profile = new AlcaldiaUserProfile($this->entityManager);

        if (!$profile->isPatrullero($this->user) || $profile->isInspeccion($this->user)) {
            return;
        }

        if ($options->get('skipPatrulleroCaseLimit') || $options->get('skipCaseStatusUpdate')) {
            return;
        }

        throw new Forbidden('Los patrulleros solo pueden consultar el caso asignado.');
    }
}
