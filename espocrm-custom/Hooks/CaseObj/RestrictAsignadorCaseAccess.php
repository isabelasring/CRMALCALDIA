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
 * Rol Asignador: no crear casos; solo editar casos post-radicados (radicado + expediente).
 */
class RestrictAsignadorCaseAccess implements BeforeSave
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

        if (!$profile->isAsignador($this->user) || $profile->isInspeccion($this->user)) {
            return;
        }

        if ($entity->isNew()) {
            throw new Forbidden('Los asignadores no pueden crear casos.');
        }

        if (!$this->isPostRadicado($entity)) {
            throw new Forbidden('Solo puede gestionar casos con radicado y expediente.');
        }
    }

    private function isPostRadicado(Entity $entity): bool
    {
        $numero = trim((string) $entity->get('cNumeroRadicado'));
        $expediente = trim((string) $entity->get('cExpediente'));

        return $numero !== '' && $expediente !== '';
    }
}
