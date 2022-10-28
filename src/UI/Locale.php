<?php

declare(strict_types=1);

namespace Cordo\Core\UI;

use Cordo\Core\Application\App;

class Locale
{
    public static function getLocale(string $lang): string
    {        
        return App::config()->get('trans.locales')[self::verifyLang($lang)];
    }

    public static function getLang(bool $isRunningInConsole) : string
    {
        $lang = $isRunningInConsole
            ? self::getConsoleLang()
            : self::getHttpLang();

        return self::verifyLang($lang);
    }

    private static function verifyLang(?string $lang): string
    {
        if (!in_array($lang, App::config()->get('trans.accepted_langs'))) {
            return App::config()->get('trans.fallback_lang');
        }

        return $lang;
    }

    private static function getHttpLang(): ?string
    {
        return $_GET['lang'] ?? null;
    }

    private static function getConsoleLang(): ?string
    {
        parse_str(implode('&', array_slice($_SERVER['argv'], 1)), $args);

        return $args['--lang'] ?? null;
    }
}
