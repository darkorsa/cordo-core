<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Queue;

abstract class QueueMessage implements QueueMessageInterface
{
    protected bool $onQueue = false;

    public function isOnQueue(): bool
    {
        return $this->onQueue;
    }

    public function pushOnQueue(): void
    {
        $this->onQueue = true;
    }
}
