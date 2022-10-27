<?php

declare(strict_types=1);

namespace Cordo\Core\Application;

use DI\Container;
use Noodlehaus\Config;
use Cordo\Core\UI\Locale;
use Cordo\Core\SharedKernel\Enum\Env;
use Cordo\Core\Application\Config\Parser;
use Cordo\Core\Application\Bootstrap\Bootstrap;

class App
{
    public Container $container;

    private Bootstrap $bootstrap;

    private static App $app;

    public readonly string $locale;

    public static function create(string $rootPath): self
    {
        return new self($rootPath, new Config($rootPath . 'config', new Parser));
    }

    public function __construct(
        public readonly string $rootPath,
        public readonly Config $config,
    ) {
        self::$app = $this;
        $this->bootstrap = new Bootstrap($this);
        $this->locale = Locale::get($this->config, defined('STDIN'));
    }

    public function init(): void
    {
        $this->container = $this->bootstrap->init();
    }

    public function register(): void
    {
        $this->bootstrap->register();
    }

    public static function rootPath(string $path): string
    {
        return self::$app->rootPath . $path;
    }

    public static function config(): Config
    {
        return self::$app->config;
    }

    public static function locale(): string
    {
        return self::$app->locale;
    }

    public static function getInstance(): self
    {
        return self::$app;
    }

    public static function isProduction(): bool
    {
        return self::$app->config->get('app.environment') == Env::PRODUCTION();
    }

    public static function isDevelopment(): bool
    {
        return self::$app->config->get('app.environment') == Env::DEV();
    }

    public function __get(string $property): mixed
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return $this->container->get($property);
    }
}
