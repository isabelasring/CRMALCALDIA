<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Custom\Tools\User\AlcaldiaUserProfile;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Exige motivo de reasignación cuando el asignador cambia el patrullero.
 */
class ValidateMotivoReasignacionOnSave implements BeforeSave
{
    public static int $order = 7;

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || $entity->isNew()) {
            return;
        }

        $profile = new AlcaldiaUserProfile($this->entityManager);

        if (!$profile->isAsignador($this->user) || $profile->isInspeccion($this->user)) {
            return;
        }

        if (!$entity->isAttributeChanged('assignedUserId')) {
            return;
        }

        $previousId = trim((string) $entity->getFetched('assignedUserId'));

        if ($previousId === '') {
            return;
        }

        $newId = trim((string) $entity->get('assignedUserId'));

        if ($newId === '' || $newId === $previousId) {
            return;
        }

        $motivo = trim((string) $entity->get('cMotivoReasignacion'));

        if ($motivo === '') {
            throw new BadRequest('Indique el motivo de reasignación.');
        }
    }
}
