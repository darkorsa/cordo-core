<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Init;

use Cordo\Core\Application\App;
use Illuminate\Validation\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;

class ValidationInit
{
    public static function init(): array
    {
        return self::getDefinitions();
    }

    private static function getDefinitions(): array
    {
        return [
            'validator_factory' => \DI\factory(function () {
                $loader = new FileLoader(new Filesystem(), App::rootPath('resources/validation/trans'));
                $translator = new Translator($loader, App::lang());

                return new Factory($translator);
            }),
        ];
    }
}
