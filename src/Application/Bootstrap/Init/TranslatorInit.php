<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Init;

use Cordo\Core\Application\App;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class TranslatorInit
{
    public static function init(): array
    {
        return self::getDefinitions();
    }

    private static function getDefinitions(): array
    {
        return [
            'translator' => \DI\factory(function () {
                $translator = new Translator(App::locale());
                $translator->addLoader('yaml', new YamlFileLoader());
                $translator->setFallbackLocales(['en']);

                return $translator;
            }),
            TranslatorInterface::class => \DI\get('translator'),
        ];
    }
}
