<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Queue;

abstract class QueueMessage implements QueueMessageInterface
{
    protected string $queue = 'default';

    protected bool $onQueue = false;

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function isOnQueue(): bool
    {
        return $this->onQueue;
    }

    public function pushOnQueue(): void
    {
        $this->onQueue = true;
    }
}
