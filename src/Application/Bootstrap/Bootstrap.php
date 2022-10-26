<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap;

use DI\Container;
use Ramsey\Uuid\Uuid;
use Noodlehaus\Config;
use DI\ContainerBuilder;
use League\Event\Emitter;
use League\Plates\Engine;
use Cordo\Core\UI\Http\Router;
use Cordo\Core\Application\App;
use League\Event\EmitterInterface;
use Cordo\Core\Application\Config\Parser;
use Psr\Http\Message\ServerRequestInterface;
use Cordo\Core\UI\Transformer\TransformerManager;
use Cordo\Core\SharedKernel\Uuid\Helper\UuidFactoryHelper;
use Cordo\Core\UI\Transformer\TransformerManagerInterface;
use Cordo\Core\Application\Bootstrap\Register\ConsoleRegister;
use Cordo\Core\Application\Bootstrap\Register\CommandBusRegister;
use Cordo\Core\Application\Bootstrap\Register\LaravelRegister;

class Bootstrap
{
    private array $definitions = [];

    private array $handlers = [];

    public function __construct(private readonly App $app)
    {
    }

    public function init(): Container
    {
        # configure uuid
        Uuid::setFactory(UuidFactoryHelper::getUuidFactory());

        # add dependency container core definitions
        $this->addDefinitions();

        # init modules
        $this->initModules();

        # init services
        $this->initServices();

        return $this->initContainer();
    }

    public function register(): void
    {
        (new LaravelRegister($this->app))->register();

        (new CommandBusRegister($this->app, $this->handlers))->register();

        (new ConsoleRegister($this->app))->register();

        $this->registerModules();
    }

    public function initContainer(): Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($this->definitions);
        $builder->useAutowiring(true);

        if (App::isProduction()) {
            $builder->enableCompilation(App::rootPath('storage/cache'));
        }

        return $builder->build();
    }

    private function addDefinitions(): void
    {
        $definitions = [
            'router' => \DI\get(Router::class),
            'config' => \DI\factory(static fn () => App::config()),
            'request' => \DI\get(ServerRequestInterface::class),
            'lang' => \DI\factory(static fn () => new Config(App::rootPath('resources/lang'), new Parser())),
            'emitter' => \DI\get(EmitterInterface::class),
            'templates' => \DI\factory(static fn () => new Engine),
            EmitterInterface::class => \DI\get(Emitter::class),
            TransformerManagerInterface::class => \DI\get(TransformerManager::class),
            Config::class => \DI\get('config'),
            ServerRequestInterface::class => \DI\factory('GuzzleHttp\Psr7\ServerRequest::fromGlobals'),
            Engine::class => \DI\get('templates'),
        ];

        $this->definitions = array_merge($this->definitions, $definitions);
    }

    private function initModules(): void
    {
        foreach ($this->app->config->get('app.modules') as $module) {
            $reflection = new \ReflectionClass($module);
            [
                $definitions,
                $handlers,
            ] = (new $module(
                $this->app,
                dirname($reflection->getFileName()) . '/',
                $reflection->getNamespaceName(),
            ))->init();

            $this->definitions = array_merge($this->definitions, $definitions);
            $this->handlers = array_merge($this->handlers, $handlers);
        }
    }

    private function initServices(): void
    {
        foreach ($this->app->config->get('app.core') as $service) {
            $this->definitions = array_merge($this->definitions, $service::init());
        }
    }

    private function registerModules(): void
    {
        foreach ($this->app->config->get('app.modules') as $module) {
            $reflection = new \ReflectionClass($module);
            (new $module(
                $this->app,
                dirname($reflection->getFileName()) . '/',
                $reflection->getNamespaceName(),
            ))->register();
        }
    }
}
