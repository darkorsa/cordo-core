<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Persistance\Doctrine\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Cordo\Core\Application\Query\QueryFilter;

abstract class QueryBuilderFilter
{
    protected $queryFilter;

    public function __construct(?QueryFilter $queryFilter)
    {
        $this->queryFilter = $queryFilter;
    }
    
    public function filter(QueryBuilder $queryBuilder): void
    {
        if (!$this->queryFilter) {
            return;
        }

        $this->doFilter($queryBuilder);

        $this->pagination($queryBuilder);
        $this->sorting($queryBuilder);
    }

    protected function pagination(QueryBuilder $queryBuilder): void
    {
        if ($this->queryFilter->getOffset() !== null) {
            $queryBuilder->setFirstResult((int) $this->queryFilter->getOffset());
        }

        if ($this->queryFilter->getLimit() !== null) {
            $queryBuilder->setMaxResults((int) $this->queryFilter->getLimit());
        }
    }

    protected function sorting(QueryBuilder $queryBuilder): void
    {
        foreach ($this->queryFilter->getSort() as $sort) {
            $queryBuilder->addOrderBy(
                $sort->column,
                $sort->direction
            );
        }
    }

    abstract protected function doFilter(QueryBuilder $queryBuilder): void;
}
