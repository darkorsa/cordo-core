<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Error;

use Exception;
use Whoops\Run;
use Monolog\Logger;
use Noodlehaus\Config;
use Rollbar\RollbarLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Cordo\Core\Application\Error\Handler\EmailErrorHandler;
use Cordo\Core\Application\Error\Handler\LoggerErrorHandler;
use Cordo\Core\Application\Error\Handler\PrettyErrorHandler;
use Cordo\Core\Infractructure\Mailer\ZendMail\MailerFactory;

class ErrorHandlerFactory
{
    public static function factory(string $handler, Config $config): ErrorHandlerInterface
    {
        if (!method_exists(self::class, $handler)) {
            throw new Exception('Unknown error handler: ' . $handler);
        }

        return self::$handler($config);
    }

    private static function log(Config $config): ErrorHandlerInterface
    {
        $formatter = new LineFormatter(
            null, // Format of message in log, default [%datetime%] %channel%.%level_name%: %message% %context% %extra%\n
            null, // Datetime format
            true, // allowInlineLineBreaks option, default false
            true  // ignoreEmptyContextAndExtra option, default false
        );
        $debugHandler = new StreamHandler($config->get('error')['channels']['log']['path'], Logger::DEBUG);
        $debugHandler->setFormatter($formatter);

        $logger = new Logger('errorlog');
        $logger->pushHandler($debugHandler);

        return new LoggerErrorHandler($logger);
    }

    private static function mail(Config $config): ErrorHandlerInterface
    {
        return new EmailErrorHandler(
            MailerFactory::factory($config->get('mail')),
            $config->get('mail')['from'],
            $config->get('error')['channels']['mail']['error_reporting_emails']
        );
    }

    private static function rollbar(Config $config): ErrorHandlerInterface
    {
        return new LoggerErrorHandler(new RollbarLogger($config->get('error')['channels']['rollbar']));
    }

    private static function verbose(Config $config): ErrorHandlerInterface
    {
        $prettyHandler = defined('STDIN')
            ? new PlainTextHandler()
            : new PrettyPageHandler();
        $whoops = new Run();
        $whoops->pushHandler($prettyHandler);

        return new PrettyErrorHandler($whoops);
    }
}
