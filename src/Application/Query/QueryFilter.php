<?php

namespace Cordo\Core\Application\Query;

use stdClass;

class QueryFilter implements QueryFilterInterface
{
    private stdClass $pagination;

    private stdClass $filter;

    private array $sort = [];

    public function __construct()
    {
        $this->pagination = (object) [
            'offset' => null,
            'limit' => null,
        ];
        $this->filter = new stdClass();
    }

    public function addFilter(string $key, string $value): self
    {
        $this->filter->$key = $value;

        return $this;
    }

    public function getFilter(string $key): ?string
    {
        return $this->filter->$key ?? null;
    }

    public function setSort(string $sortBy): self
    {
        self::sort($sortBy, $this);

        return $this;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    public function setOffset(int $offset): self
    {
        $this->pagination->offset = $offset;

        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->pagination->offset;
    }

    public function setLimit(int $limit): self
    {
        $this->pagination->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->pagination->limit;
    }

    public static function fromQueryParams(array $params): self
    {
        $queryFilter = new self();

        self::filters($params['filter'] ?? [], $queryFilter);
        self::pagination($params['page'] ?? [], $queryFilter);
        self::sort($params['sort'] ?? null, $queryFilter);

        return $queryFilter;
    }

    private static function filters(array $filters, self $queryFilter): void
    {
        foreach ($filters as $key => $value) {
            $queryFilter->filter->$key = $value;
        }
    }

    private static function pagination(array $page, self $queryFilter): void
    {
        $queryFilter->pagination->offset = isset($page['offset'])
            ? (int) $page['offset']
            : null;
        $queryFilter->pagination->limit = isset($page['limit'])
            ? (int) $page['limit']
            : null;
    }

    private static function sort(?string $sortBy, self $queryFilter): void
    {
        if (is_null($sortBy)) {
            return;
        }

        $sorts = explode(',', $sortBy);

        foreach ($sorts as $sort) {
            $queryFilter->sort[] = (object) [
                'direction' => $sort[0] == '-' ? 'DESC' : 'ASC',
                'column' => trim($sort, '-')
            ];
        }
    }
}
