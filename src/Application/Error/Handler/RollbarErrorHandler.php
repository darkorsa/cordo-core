<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Error\Handler;

use Throwable;
use Rollbar\Payload\Level;
use Rollbar\RollbarLogger;
use Cordo\Core\Application\Error\ErrorHandlerInterface;

class RollbarErrorHandler implements ErrorHandlerInterface
{
    private RollbarLogger $logger;

    public function __construct(RollbarLogger $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $exception): void
    {
        // The exception is passed as is, so that Rollbar builds a trace payload with stack
        // frames instead of a plain message item - grouping of items depends on it.
        $this->logger->report(Level::ERROR, $exception);

        // Error handlers may exit right after reporting, so a batched queue would never be sent.
        if ($this->logger->getConfig()->getBatched()) {
            $this->logger->flush();
        }
    }
}
