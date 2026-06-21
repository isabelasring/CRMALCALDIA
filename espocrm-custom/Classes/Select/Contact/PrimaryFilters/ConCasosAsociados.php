<?php

namespace Espo\Custom\Classes\Select\Contact\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\Custom\Tools\Party\PartyCaseFilterHelper;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;

class ConCasosAsociados implements Filter
{
    public function __construct(
        private PartyCaseFilterHelper $helper
    ) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        $ids = $this->helper->findContactIdsWithCases();

        if ($ids === []) {
            $queryBuilder->where([Attribute::ID => null]);

            return;
        }

        $queryBuilder->where([
            Attribute::ID => $ids,
        ]);
    }
}
