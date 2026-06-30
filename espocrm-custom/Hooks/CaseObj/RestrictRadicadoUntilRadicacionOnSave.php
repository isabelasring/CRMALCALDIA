<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Custom\Tools\CaseObj\CaseRadicadoHelper;
use Espo\Custom\Tools\User\AlcaldiaUserProfile;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Sin rol Radicación: no persistir radicado/expediente hasta radicación oficial.
 * El caso solo queda en BD; el Excel se llena después (hook ExportCaseExcelAlcaldiaOnSave).
 */
class RestrictRadicadoUntilRadicacionOnSave implements BeforeSave
{
    public static int $order = 4;

    public function __construct(
        private User $user,
        private AlcaldiaUserProfile $profile
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || $this->profile->canEditRadicado($this->user)) {
            return;
        }

        if (CaseRadicadoHelper::isRadicadoCompleto($entity)) {
            CaseRadicadoHelper::restoreRadicadoFromFetched($entity);

            return;
        }

        CaseRadicadoHelper::clearRadicadoFields($entity);
    }
}
