<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Error;

use Exception;
use Throwable;

class ErrorReporter implements ErrorReporterInterface
{
    private $handlers = [];

    private const ERRORS_TO_HANDLE = [
        E_ERROR,
        E_PARSE,
        E_COMPILE_ERROR,
        E_CORE_ERROR,
        E_USER_ERROR,
    ];

    public function report(Throwable $exception): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($exception);
        }
    }

    public function pushHandler(ErrorHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * @return bool true when handled, so PHP's internal handler does not run as well
     */
    public function errorHandler($code, $message, $file, $line): bool
    {
        // Levels silenced by error_reporting() or the @ operator must not be handled.
        if (!(error_reporting() & $code)) {
            return true;
        }

        $isFatal = in_array($code, self::ERRORS_TO_HANDLE, true);

        $this->reportError($code, $message, $file, $line, $isFatal);

        // Warnings, notices and deprecations are recoverable: report, but keep going.
        // Exiting on them turns any library deprecation into a 500, and silently kills
        // long-running processes such as queue workers.
        if (!$isFatal) {
            return true;
        }

        http_response_code(500);
        exit;
    }

    public function fatalErrorShutdownHandler(): void
    {
        $lastError = error_get_last();

        if (!$lastError || !in_array($lastError['type'], self::ERRORS_TO_HANDLE, true)) {
            return;
        }

        // Deliberately bypasses errorHandler(): the error already ended the process,
        // so error_reporting() must not suppress the report.
        $this->reportError(
            $lastError['type'],
            $lastError['message'],
            $lastError['file'],
            $lastError['line'],
            true
        );

        http_response_code(500);
        exit;
    }

    private function reportError(int $code, string $message, string $file, int $line, bool $isFatal): void
    {
        $label = $isFatal ? 'Fatal error' : 'Error';

        $message = "{$label} with code {$code} occured in file: {$file} on line {$line}. "
            . "Error message: {$message}";

        try {
            throw new Exception($message);
        } catch (Exception $exception) {
            $this->report($exception);
        }
    }

    public function exceptionHandler(Throwable $exception): void
    {
        $this->report($exception);

        http_response_code(500);
        exit;
    }
}
