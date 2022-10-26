<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Register;

use Noodlehaus\Config;
use Cordo\Core\Application\App;
use Illuminate\Events\Dispatcher;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\DatabaseManager;
use Cordo\Core\Application\Config\Parser;
use Illuminate\Events\EventServiceProvider;
use Medforum\UI\Validator\ValidatorFactory;
use Cordo\Core\Application\Queue\QueueHandler;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Database\DatabaseTransactionsManager;
use Cordo\Core\Application\Bootstrap\Laravel\Laravel;
use Illuminate\Database\Connectors\ConnectionFactory;
use Cordo\Core\Application\Bootstrap\Laravel\ExceptionHandler;

class LaravelRegister
{
    public function __construct(private App $app)
    {
    }

    public function register(): void
    {
        $app = Laravel::getInstance();
        $app->bind('exception.handler', ExceptionHandler::class);
        $app->singleton('config', function () {
            $config = new Config(__DIR__ . '/../Laravel/config', new Parser);
            return $config->merge(App::config());
        });
        // $app->singleton('validator', function ($app) {
        //     return (new ValidatorFactory())->getFactory();
        // });
        $app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });
        $app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });
        $app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
        $app->singleton('db.transactions', function ($app) {
            return new DatabaseTransactionsManager;
        });

        $this->queues($app);

        /** @phpstan-ignore-next-line */
        Facade::setFacadeApplication($app);

        $this->app->container->set('laravel', $app);
    }

    private function queues(Laravel $app): void
    {
        /** @phpstan-ignore-next-line */
        (new EventServiceProvider($app))->register();

        $app->instance('Illuminate\Contracts\Events\Dispatcher', new Dispatcher($app));
        $app->bind('redis', function () use ($app) {
            /** @phpstan-ignore-next-line */
            return new RedisManager($app, $app['config']->get('database.redis.client'), [
                'default' => $app['config']->get('database.redis.default'),
            ]);
        });

        $queue = new Queue($app);

        $app['queue'] = $queue->getQueueManager();
        $app[QueueHandler::class] = new QueueHandler(App::getInstance()->container);
    }
}
