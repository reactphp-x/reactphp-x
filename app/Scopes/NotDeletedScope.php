<?php

namespace App\Scopes;

use Cycle\ORM\Select;

class NotDeletedScope implements Select\ScopeInterface
{
    public function apply(Select\QueryBuilder $query): void
    {
        $query->where('deleted_at', '=', null);
    }
}