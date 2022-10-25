<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Init;

use Cordo\Core\Application\App;
use Cordo\Core\Infractructure\Mailer\ZendMail\MailerFactory;
use Cordo\Core\Infractructure\Mailer\ZendMail\MailerInterface;

class MailerInit
{
    public static function init(): array
    {
        return self::getDefinitions();
    }

    private static function getDefinitions(): array
    {
        return [
            'mailer' => \DI\factory(fn () => MailerFactory::factory(App::config()->get('mail'))),
            MailerInterface::class => \DI\get('mailer'),
        ];
    }
}
