<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Error;

use Noodlehaus\Config;
use Cordo\Core\Application\Error\ErrorReporterInterface;

class ErrorReporterBuilder
{
    private ErrorReporterInterface $errorReporter;

    private Config $config;

    public function __construct(ErrorReporterInterface $errorReporter, Config $config)
    {
        $this->errorReporter = $errorReporter;
        $this->config = $config;
    }

    public function build(): ErrorReporterInterface
    {
        $stack = $this->config->get('error')['stacks'][$this->config->get('app.environment')];
        if ($this->config->get('app.debug')) {
            $stack[] = 'verbose';
        }

        foreach ($stack as $channel) {
            $handler = ErrorHandlerFactory::factory($channel, $this->config);
            $this->errorReporter->pushHandler($handler);
        }

        return $this->errorReporter;
    }
}
