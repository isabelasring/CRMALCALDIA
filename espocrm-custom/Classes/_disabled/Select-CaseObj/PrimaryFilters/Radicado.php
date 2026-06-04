<?php

namespace Espo\Custom\Classes\Select\CaseObj\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\SelectBuilder;

class Radicado implements Filter
{
    private const STATUS_RADICADO = 'Radicado';

    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where(['status' => self::STATUS_RADICADO]);
    }
}
