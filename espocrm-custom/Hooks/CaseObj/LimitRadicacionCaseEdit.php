<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Custom\Tools\CaseObj\CaseRadicadoHelper;
use Espo\Custom\Tools\User\AlcaldiaUserProfile;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Rol Radicación: solo puede persistir cambios en campos de radicado/expediente.
 */
class LimitRadicacionCaseEdit implements BeforeSave
{
    public static int $order = 5;

    public function __construct(
        private User $user,
        private AlcaldiaUserProfile $profile
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        if (!$this->profile->isOperationalRadicacion($this->user)) {
            return;
        }

        if ($entity->isNew()) {
            return;
        }

        $editable = array_flip(CaseRadicadoHelper::FIELD_LIST);

        foreach ($entity->getAttributeList() as $attribute) {
            if (isset($editable[$attribute])) {
                continue;
            }

            if (!$entity->isAttributeChanged($attribute)) {
                continue;
            }

            $entity->set($attribute, $entity->getFetched($attribute));
        }
    }
}
