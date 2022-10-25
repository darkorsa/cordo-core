<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Init;

use Cordo\Core\Application\App;
use Cordo\Core\Application\Error\ErrorReporter;
use Cordo\Core\Application\Error\ErrorReporterBuilder;
use Cordo\Core\Application\Error\ErrorReporterInterface;

class ErrorHandlerInit
{
    public static ErrorReporterInterface $errorReporter;
    
    public static function init(): array
    {
        self::setDisplayErrors(App::config()->get('app.debug'));
        self::$errorReporter = self::setErrorHandler();

        return self::getDefinitions();
    }

    private static function setDisplayErrors(bool $debug): void
    {
        ini_set('display_errors', (string) $debug);
        ini_set('display_startup_errors', (string) $debug);
    }

    public static function setErrorHandler(): ErrorReporterInterface
    {
        $errorReporter = (new ErrorReporterBuilder(
            new ErrorReporter(),
            App::config(),
        ))->build();

        // set php error handlers
        set_error_handler([$errorReporter, 'errorHandler']);
        register_shutdown_function([$errorReporter, 'fatalErrorShutdownHandler']);
        set_exception_handler([$errorReporter, 'exceptionHandler']);

        return $errorReporter;
    }

    private static function getDefinitions(): array
    {
        return [
            'error_reporter' => \DI\factory(fn () => ErrorHandlerInit::$errorReporter),
            ErrorReporterInterface::class => \DI\get('error_reporter'),
        ];
    }
}
