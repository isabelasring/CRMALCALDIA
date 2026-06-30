<?php

namespace Espo\Custom\Classes\RecordHooks\CaseObj;

use Espo\Core\Record\Hook\SaveHook;
use Espo\ORM\Entity;

/**
 * Al crear un caso: sin patrullero asignado ni equipos por defecto.
 */
class EarlyBeforeCreate implements SaveHook
{
    public function process(Entity $entity): void
    {
        $entity->set('assignedUserId', null);
        $entity->set('assignedUserName', null);
        $entity->setLinkMultipleIdList('teams', []);
    }
}
