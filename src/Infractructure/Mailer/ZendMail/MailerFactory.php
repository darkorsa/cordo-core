<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Mailer\ZendMail;

use InvalidArgumentException;

class MailerFactory
{
    public static function factory(array $config): MailerInterface
    {
        switch ($config['driver']) {
            case 'log':
                return new LogMailer($config['drivers']['log']['path']);
            case 'smtp':
                $smtp = $config['drivers']['smtp'];
                return new SmtpMailer(
                    $smtp['host'],
                    $smtp['port'],
                    $smtp['username'],
                    $smtp['password'],
                    $smtp['encryption'],
                );
            default:
                throw new InvalidArgumentException("Unknown mailer driver: " . $config['driver']);
        }
    }
}
