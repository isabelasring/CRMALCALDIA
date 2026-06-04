<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Patrulleros: solo editan casos Radicado asignados a ellos y solo campos del acta de visita.
 * Otros usuarios no pueden modificar campos del acta.
 */
class PatrulleroCaseAccess implements BeforeSave
{
    public static int $order = 8;

    private const STATUS_RADICADO = 'Radicado';
    private const TEAM_PATRULLEROS = 'Patrulleros';

    private const ACTA_FIELDS = [
        'cActaFechaVisita',
        'cActaHoraVisita',
        'cActaDireccionVisita',
        'cActaNombreVisitado',
        'cActaDocumentoVisitado',
        'cActaHallazgos',
        'cActaMedidasTomadas',
        'cActaObservaciones',
    ];

    private const BLOCKED_ROLES = [
        'Asignador',
        'Radicación',
        'Inspección',
        'Inspeccion',
    ];

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || $this->user->isPortal()) {
            $this->stripActaChangesForNonPatrullero($entity);

            return;
        }

        if ($this->isPatrulleroUser()) {
            $this->guardPatrulleroSave($entity);
            $this->limitToActaFields($entity);
            $this->updateActaEstado($entity);

            return;
        }

        $this->stripActaChangesForNonPatrullero($entity);
    }

    private function guardPatrulleroSave(Entity $entity): void
    {
        if ($entity->isNew()) {
            throw Forbidden::create('Los patrulleros no pueden crear casos.');
        }

        if ($entity->get('status') !== self::STATUS_RADICADO) {
            throw Forbidden::create('Solo puede diligenciar el acta en casos Radicado.');
        }

        if ($entity->get('assignedUserId') !== $this->user->getId()) {
            throw Forbidden::create('Solo puede diligenciar casos asignados a usted.');
        }
    }

    private function limitToActaFields(Entity $entity): void
    {
        foreach ($entity->getAttributeList() as $attribute) {
            if (!$entity->isAttributeChanged($attribute)) {
                continue;
            }

            if (in_array($attribute, self::ACTA_FIELDS, true)) {
                continue;
            }

            if ($entity->hasFetched($attribute)) {
                $entity->set($attribute, $entity->getFetched($attribute));
            }
        }
    }

    private function stripActaChangesForNonPatrullero(Entity $entity): void
    {
        foreach (self::ACTA_FIELDS as $field) {
            if (!$entity->isAttributeChanged($field)) {
                continue;
            }

            if ($entity->hasFetched($field)) {
                $entity->set($field, $entity->getFetched($field));
            }
        }
    }

    private function updateActaEstado(Entity $entity): void
    {
        foreach (self::ACTA_FIELDS as $field) {
            $value = $entity->get($field);

            if (is_string($value) && trim($value) !== '') {
                $entity->set('cActaEstado', 'Diligenciada');

                return;
            }

            if ($value !== null && $value !== '' && !is_string($value)) {
                $entity->set('cActaEstado', 'Diligenciada');

                return;
            }
        }

        if (!$entity->has('cActaEstado') || $entity->get('cActaEstado') === null) {
            $entity->set('cActaEstado', 'Pendiente');
        }
    }

    private function isPatrulleroUser(): bool
    {
        if (!$this->isInPatrullerosTeam()) {
            return false;
        }

        foreach (self::BLOCKED_ROLES as $roleName) {
            if ($this->hasRole($roleName)) {
                return false;
            }
        }

        return true;
    }

    private function isInPatrullerosTeam(): bool
    {
        $team = $this->entityManager
            ->getRDBRepositoryByClass(Team::class)
            ->where(['name' => self::TEAM_PATRULLEROS])
            ->findOne();

        if (!$team) {
            return false;
        }

        $teams = $this->user->getLinkMultipleIdList('teams') ?? [];

        return in_array($team->getId(), $teams, true);
    }

    private function hasRole(string $roleName): bool
    {
        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => $roleName])
            ->findOne();

        if (!$role) {
            return false;
        }

        $roles = $this->user->getLinkMultipleIdList('roles') ?? [];

        return in_array($role->getId(), $roles, true);
    }
}
