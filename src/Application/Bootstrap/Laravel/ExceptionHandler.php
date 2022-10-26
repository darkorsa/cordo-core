<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Laravel;

use Throwable;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;

class ExceptionHandler implements ExceptionHandlerInterface
{
    public function shouldReport(Throwable $e): bool
    {
        return false;
    }

    public function report(Throwable $e): void
    {
        throw $e;
    }

    /** @phpstan-ignore-next-line */
    public function render($request, Throwable $e): Response
    {
        throw $e;
    }

    public function renderForConsole($output, Throwable $e): void
    {
        throw $e;
    }
}
