<?php

declare(strict_types=1);

namespace Cordo\Core\UI;

use Noodlehaus\Config;

class Locale
{
    public static function get(Config $config, bool $isRunningInConsole): string
    {
        $lang = $isRunningInConsole
            ? self::getConsoleLang()
            : self::getHttpLang();

        return self::getLocaleForLang($lang, $config);
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

    private static function getLocaleForLang(?string $lang, Config $config): string
    {
        if (!in_array($lang, $config->get('trans.accepted_langs'))) {
            return $config->get('trans.fallback_locale');
        }

        return $config->get('trans.locales')[$lang];
    }
}
