<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Config;

use Exception;
use Noodlehaus\Exception\ParseException;

class ModuleParser extends \Noodlehaus\Parser\Php
{
    protected string $context;

    protected string $module;

    public function __construct($context, $module)
    {
        $this->context = $context;
        $this->module = $module;
    }

    public function parseFile($filename): array
    {
        $data = [];

        // Run the fileEval the string, if it throws an exception, rethrow it
        try {
            $data[$this->context . '_' . $this->module . '::' . basename($filename, ".php")] = require $filename;
        } catch (Exception $exception) {
            throw new ParseException(
                [
                    'message'   => 'PHP file threw an exception',
                    'exception' => $exception,
                ]
            );
        }
        // Complete parsing
        return (array) $this->parse($data);
    }
}
