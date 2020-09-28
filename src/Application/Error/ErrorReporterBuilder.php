<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Error;

use Noodlehaus\Config;
use Cordo\Core\Application\Error\ErrorReporterInterface;

class ErrorReporterBuilder
{
    private ErrorReporterInterface $errorReporter;

    private Config $config;

    private string $env;

    private bool $debug;

    public function __construct(ErrorReporterInterface $errorReporter, Config $config, string $env, bool $debug)
    {
        $this->errorReporter = $errorReporter;
        $this->config = $config;
        $this->env = $env;
        $this->debug = $debug;
    }

    public function build(): ErrorReporterInterface
    {
        $stack = $this->config->get('error')['stacks'][$this->env];
        if ($this->debug) {
            $stack[] = 'verbose';
        }

        foreach ($stack as $channel) {
            $handler = ErrorHandlerFactory::factory($channel, $this->config);
            $this->errorReporter->pushHandler($handler);
        }

        return $this->errorReporter;
    }
}
