<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Patrullero diligencia acta → caso pasa a Visita realizada (pendiente revisión Juan).
 */
class SetVisitaRealizadaOnActaDiligenciada implements BeforeSave
{
    public static int $order = 18;

    private const STATUS_EN_PROCESO = 'En proceso';
    private const STATUS_VISITA_REALIZADA = 'Visita realizada';
    private const ACTA_DILIGENCIADA = 'Diligenciada';
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

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || !$this->isPatrulleroUser()) {
            return;
        }

        if ($entity->get('status') !== self::STATUS_EN_PROCESO) {
            return;
        }

        if ($entity->get('assignedUserId') !== $this->user->getId()) {
            return;
        }

        if (!$this->hasActaContent($entity)) {
            return;
        }

        $entity->set('cActaEstado', self::ACTA_DILIGENCIADA);
        $entity->set('status', self::STATUS_VISITA_REALIZADA);
        $entity->set('cActaRegistroOficial', false);
        $entity->set('cActaVistoBueno', false);
    }

    private function hasActaContent(Entity $entity): bool
    {
        foreach (self::ACTA_FIELDS as $field) {
            $value = $entity->get($field);

            if (is_string($value) && trim($value) !== '') {
                return true;
            }

            if ($value !== null && $value !== '' && !is_string($value)) {
                return true;
            }
        }

        return false;
    }

    private function isPatrulleroUser(): bool
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
}
