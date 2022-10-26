<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Queue;

use League\Tactician\Middleware;
use Illuminate\Queue\QueueManager;
use Medforum\Application\Queue\QueueHandler;

/**
 * Sends the command to a remote location using message queues
 */
final class QueueMiddleware implements Middleware
{
    public function __construct(
        private QueueManager $queue,
        private string $connection
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function execute($command, callable $next)
    {
        if ($command instanceof QueueMessageInterface && !$command->isOnQueue()) {
            $command->pushOnQueue();
            $this->queue->connection($this->connection)->push(QueueHandler::class, serialize($command));
            return;
        }

        return $next($command);
    }
}
