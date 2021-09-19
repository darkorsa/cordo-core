<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Persistance\Doctrine\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Common\Collections\ArrayCollection;
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

        return (string) $this->connection->fetchOne($queryBuilder->getSQL(), $queryBuilder->getParameters());
    }

    protected function assoc(QueryBuilder $queryBuilder, ?QueryBuilderFilter $filter = null): array
    {
        $this->filter($filter, $queryBuilder);

        $return = $this->connection->fetchAssociative($queryBuilder->getSQL(), $queryBuilder->getParameters());

        if (!$return) {
            throw new ResourceNotFoundException("Cannot get resource");
        }

        return $return;
    }

    protected function all(QueryBuilder $queryBuilder, ?QueryBuilderFilter $filter = null): array
    {
        $this->filter($filter, $queryBuilder);

        return $this->connection->fetchAllAssociative($queryBuilder->getSQL(), $queryBuilder->getParameters());
    }

    protected function createCollection(array $result, string $view): ArrayCollection
    {
        $collection = new ArrayCollection();
        array_map(static function (array $data) use ($collection, $view) {
            // @phpstan-ignore-next-line
            $collection->add(call_user_func($view . '::fromArray', $data));
        }, $result);

        return $collection;
    }

    protected function filter(?QueryBuilderFilter $queryFilter, QueryBuilder $queryBuilder)
    {
        if (!is_null($queryFilter)) {
            $queryFilter->filter($queryBuilder);
        }
    }
}
