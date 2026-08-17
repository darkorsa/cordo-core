<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Persistance\Doctrine\Query;

use InvalidArgumentException;
use Doctrine\DBAL\Query\QueryBuilder;
use Cordo\Core\Application\Query\QueryFilterInterface;

abstract class QueryBuilderFilter
{
    /**
     * Column name accepted from user input. addOrderBy() pastes its argument into SQL
     * without escaping, so only a plain identifier is allowed through - optionally
     * qualified with a table/alias prefix (table.column), which quoteIdentifier()
     * quotes segment by segment.
     */
    private const SORT_COLUMN = '/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/';

    protected $queryFilter;

    public function __construct(?QueryFilterInterface $queryFilter)
    {
        $this->queryFilter = $queryFilter;
    }

    public function filter(QueryBuilder $queryBuilder, bool $fetchOne): void
    {
        if (!$this->queryFilter) {
            return;
        }

        $this->doFilter($queryBuilder);

        if (!$fetchOne) {
            $this->pagination($queryBuilder);
            $this->sorting($queryBuilder);
        }
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

    final protected function sorting(QueryBuilder $queryBuilder): void
    {
        foreach ($this->queryFilter->getSort() as $sort) {
            $column = (string) $sort->column;

            if (!preg_match(self::SORT_COLUMN, $column)) {
                throw new InvalidArgumentException("Invalid sort column: '{$column}'");
            }

            $queryBuilder->addOrderBy(
                $queryBuilder->getConnection()->quoteIdentifier($column),
                $sort->direction === 'DESC' ? 'DESC' : 'ASC'
            );
        }
    }

    protected function getFilter(string $key, bool $required = false)
    {
        if (!$this->queryFilter) {
            return null;
        }

        return $this->queryFilter->getFilter($key, $required);
    }

    abstract protected function doFilter(QueryBuilder $queryBuilder): void;
}
