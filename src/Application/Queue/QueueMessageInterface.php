<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Queue;

interface QueueMessageInterface
{
    public function getQueue(): string;

    public function isOnQueue(): bool;

    public function pushOnQueue(): void;
}
