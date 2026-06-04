<?php

namespace Espo\Custom\Classes\Select\User\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\Entities\Team;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder;

/**
 * Usuarios activos del equipo Recibidores (Recibida por).
 */
class Recibidores implements Filter
{
    private const TEAM_RECIBIDORES = 'Recibidores';

    public function __construct(private EntityManager $entityManager) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        $team = $this->entityManager
            ->getRDBRepositoryByClass(Team::class)
            ->where(['name' => self::TEAM_RECIBIDORES])
            ->findOne();

        if (!$team) {
            $queryBuilder->where(['id' => null]);

            return;
        }

        $queryBuilder->where([
            'isActive' => true,
            'type' => 'regular',
            'id=s' => [
                'from' => 'TeamUser',
                'select' => ['userId'],
                'whereClause' => [
                    'teamId' => $team->getId(),
                    'deleted' => false,
                ],
            ],
        ]);
    }
}
