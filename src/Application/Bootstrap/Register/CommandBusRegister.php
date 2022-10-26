<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Register;

use Monolog\Logger;
use Bernard\Producer;
use Cordo\Core\Application\App;
use League\Tactician\CommandBus;
use Monolog\Handler\StreamHandler;
use League\Tactician\Logger\LoggerMiddleware;
use League\Tactician\Plugins\LockingMiddleware;
use League\Tactician\Container\ContainerLocator;
use Cordo\Core\Application\Queue\QueueMiddleware;
use League\Tactician\CommandEvents\EventMiddleware;
use League\Tactician\Handler\CommandHandlerMiddleware;
use Symfony\Component\EventDispatcher\EventDispatcher;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use League\Tactician\Logger\Formatter\ClassNameFormatter;
use League\Tactician\Handler\MethodNameInflector\InvokeInflector;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;

class CommandBusRegister
{
    public function __construct(private App $app, private array $handlers)
    {
    }

    public function register(): void
    {
        $commandHandlerMiddleware = new CommandHandlerMiddleware(
            new ClassNameExtractor(),
            new ContainerLocator($this->app->container, $this->handlers),
            new InvokeInflector()
        );

        $commandLogger = new Logger('command');
        $commandLogger->pushHandler(new StreamHandler($this->app->rootPath('logs/command.log'), Logger::DEBUG));

        $commandBus = new CommandBus([
            new LoggerMiddleware(new ClassNameFormatter(), $commandLogger),
            new LockingMiddleware(),
            new TransactionMiddleware($this->app->entity_manager),
            new QueueMiddleware($this->app->laravel['queue'], $this->app->config->get('queue.default')),
            new EventMiddleware($this->app->emitter),
            $commandHandlerMiddleware,
        ]);

        $this->app->container->set('command_bus', $commandBus);
        $this->app->container->set('handlers_map', $this->handlers);
    }
}
