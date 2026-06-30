<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Custom\Tools\User\AlcaldiaUserProfile;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Custom\Tools\User\AlcaldiaRolesConfig;

/**
 * Rol Asignador (Julian): con radicado y expediente, solo puede cambiar Asignado a.
 */
class LimitAsignadorCaseEdit implements BeforeSave
{
    public static int $order = 8;

    private const ALLOWED = ['assignedUserId', 'assignedUserName', 'cMotivoReasignacion'];

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (AlcaldiaRolesConfig::isDisabled()) {
            return;
        }

        if ($this->user->isAdmin() || $entity->isNew()) {
            return;
        }

        $profile = new AlcaldiaUserProfile($this->entityManager);

        if (!$profile->isAsignador($this->user) || $profile->isInspeccion($this->user)) {
            return;
        }

        if (!$this->isPostRadicado($entity)) {
            return;
        }

        foreach ($entity->getAttributeList() as $attribute) {
            if (!$entity->isAttributeChanged($attribute)) {
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

    private function isPostRadicado(Entity $entity): bool
    {
        $numero = trim((string) $entity->get('cNumeroRadicado'));
        $expediente = trim((string) $entity->get('cExpediente'));

        return $numero !== '' && $expediente !== '';
    }
}
