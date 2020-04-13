<?php

namespace Cordo\Core\Application\Query;

interface QueryFilterInterface
{
    public function addFilter(string $key, string $value): self;

    public function getFilter(string $key): ?string;

    public function setSort(string $sortBy): self;

    public function getSort(): array;

    public function setOffset(int $offset): self;

    public function getOffset(): ?int;

    public function setLimit(int $limit): self;

    public function getLimit(): ?int;

    public static function fromQueryParams(array $params): self;
}
