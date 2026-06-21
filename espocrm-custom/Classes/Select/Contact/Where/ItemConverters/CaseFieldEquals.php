<?php

namespace Espo\Custom\Classes\Select\Contact\Where\ItemConverters;

use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Custom\Tools\Party\PartyCaseFilterHelper;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

class CaseFieldEquals implements ItemConverter
{
    public function __construct(
        private PartyCaseFilterHelper $helper
    ) {}

    public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
    {
        $attribute = $item->getAttribute() ?? '';
        $caseWhere = $this->helper->buildCaseWhere($attribute, $item->getValue(), $item->getType() ?? 'equals');

        if ($caseWhere === []) {
            return WhereClause::fromRaw([Attribute::ID => null]);
        }

        $ids = $this->helper->findContactIdsByCaseCriteria($caseWhere);

        if ($ids === []) {
            return WhereClause::fromRaw([Attribute::ID => null]);
        }

        return WhereClause::fromRaw([
            Attribute::ID => $ids,
        ]);
    }
}
