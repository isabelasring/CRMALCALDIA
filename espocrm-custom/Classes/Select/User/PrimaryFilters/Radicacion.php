<?php

namespace Espo\Custom\Classes\Select\User\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\Entities\Role;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder;

/**
 * Usuarios activos con rol Radicación (Remitido a → Edwin).
 */
class Radicacion implements Filter
{
    private const ROLE_RADICACION = 'Radicación';

    public function __construct(private EntityManager $entityManager) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => self::ROLE_RADICACION])
            ->findOne();

        if (!$role) {
            $queryBuilder->where(['id' => null]);

            return;
        }

        $queryBuilder->where([
            'isActive' => true,
            'type' => 'regular',
            'id=s' => [
                'from' => 'RoleUser',
                'select' => ['userId'],
                'whereClause' => [
                    'roleId' => $role->getId(),
                    'deleted' => false,
                ],
            ],
        ]);
    }
}
