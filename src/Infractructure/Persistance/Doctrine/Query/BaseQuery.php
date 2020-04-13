<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Persistance\Doctrine\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Cordo\Core\Application\Exception\ResourceNotFoundException;

abstract class BaseQuery
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    protected function createQB(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }

    protected function column(QueryBuilder $queryBuilder, ?QueryBuilderFilter $filter = null): string
    {
        $this->filter($filter, $queryBuilder);

        return (string) $this->connection->fetchColumn($queryBuilder->getSQL(), $queryBuilder->getParameters());
    }

    protected function assoc(QueryBuilder $queryBuilder, ?QueryBuilderFilter $filter = null): array
    {
        $this->filter($filter, $queryBuilder);

        $return = $this->connection->fetchAssoc($queryBuilder->getSQL(), $queryBuilder->getParameters());

        if (!$return) {
            throw new ResourceNotFoundException("Cannot get resource");
        }

        return $return;
    }

    protected function all(QueryBuilder $queryBuilder, ?QueryBuilderFilter $filter = null): array
    {
        $this->filter($filter, $queryBuilder);

        return $this->connection->fetchAll($queryBuilder->getSQL(), $queryBuilder->getParameters());
    }

    protected function filter(?QueryBuilderFilter $queryFilter, QueryBuilder $queryBuilder)
    {
        if (!is_null($queryFilter)) {
            $queryFilter->filter($queryBuilder);
        }
    }
}
