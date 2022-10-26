<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Queue;

use Illuminate\Contracts\Queue\Job;
use Psr\Container\ContainerInterface;

class QueueHandler
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function fire(Job $job, string $command): void
    {
        $this->container->get('command_bus')->handle(unserialize($command));
        $job->delete();
    }
}
