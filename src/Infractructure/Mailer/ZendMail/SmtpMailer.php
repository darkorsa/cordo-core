<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Mailer\ZendMail;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;

class SmtpMailer implements MailerInterface
{
    private $host;

    private $port;

    private $username;

    private $password;

    private $encryption;

    public function __construct(string $host, string $port, string $username, string $password, string $encryption)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
    }

    public function send(Message $message): void
    {
        $transport = new Smtp();
        $options   = new SmtpOptions([
            'host'              => $this->host,
            'port'              => $this->port,
            'connection_class'  => 'login',
            'connection_config' => [
                'username' => $this->username,
                'password' => $this->password,
                'ssl' => $this->encryption,
            ],
        ]);

        $transport->setOptions($options);
        $transport->send($message);
    }
}
