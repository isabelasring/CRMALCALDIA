<?php

namespace Espo\Custom\Hooks\User;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Custom\Tools\User\TeamRoleSync;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

class SyncTeamsFromRoles implements AfterSave
{
    public function __construct(
        private TeamRoleSync $teamRoleSync
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if ($options->get('skipTeamRoleSync')) {
            return;
        }

        if (!$entity->isAttributeChanged('rolesIds')) {
            return;
        }

        $this->teamRoleSync->syncUser($entity);
    }
}
