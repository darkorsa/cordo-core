<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Register;

use Noodlehaus\Config;
use RecursiveIteratorIterator;
use Cordo\Core\Application\App;
use RecursiveDirectoryIterator;
use Cordo\Core\Application\Config\ModuleParser;

abstract class ModuleRegister
{
    protected string $context;

    protected string $module;

    public function __construct(protected App $app, protected string $modulePath, protected string $namespace,)
    {
        [$_, $context, $module] = explode('\\', $namespace);

        $this->context = $context;
        $this->module = $module;
    }

    public function init(): array
    {
        return [
            $this->getDefinitions(),
            $this->getHandlers(),
        ];
    }

    public function register(): void
    {
        $this->loadRoutes();
        $this->loadConfigs();
        $this->loadTranslations();
        $this->loadListeners();
        $this->loadViews();
        $this->loadAclRules();
        $this->loadCommands();
    }

    protected function getDefinitions(): array
    {
        $path = $this->modulePath('Application/definitions.php');

        if (file_exists($path)) {
            return include $path;
        }

        return [];
    }

    protected function getHandlers(): array
    {
        $path = $this->modulePath('Application/handlers.php');

        if (file_exists($path)) {
            return include $path;
        }

        return [];
    }

    protected function loadRoutes(): void
    {
        $className = "{$this->namespace}\UI\Http\Route\\{$this->module}Routes";

        if (!class_exists($className)) {
            return;
        }

        $register = new $className(
            $this->app->router,
            $this->app->container,
            $this->resource(),
        );
        $register->register();
    }

    protected function loadConfigs(): void
    {
        $path = $this->modulePath('Application/config/');

        if (!file_exists($path)) {
            return;
        }

        $configParser = new ModuleParser(strtolower($this->context), strtolower($this->module));

        $this->app->config->merge(new Config($path, $configParser));
    }

    protected function loadTranslations(): void
    {
        $translationsPath = $this->modulePath('/UI/trans');

        if (!file_exists($translationsPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($translationsPath));

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            if (preg_match('/([a-z0-9]+)\.([a-z]{2})\.yaml/', $file->getPathname(), $matches)) {
                $locale = $this->app->config->get('trans.locales')[$matches[2]] ?? null;
                if (!$locale) {
                    continue;
                }

                $this->app->translator->addResource('yaml', $file->getPathname(), $locale, $matches[1]);
            }
        }
    }

    protected function loadListeners(): void
    {
        $className = "{$this->namespace}\Application\Event\Register\\{$this->module}Listeners";;

        if (!class_exists($className)) {
            return;
        }

        (new $className(
            $this->app->emitter,
            $this->app->container,
            strtolower($this->module)
        ))->register();
    }

    protected function loadViews(): void
    {
        $viewsPath = $this->modulePath('/UI/views');

        if (file_exists($viewsPath)) {
            $this->app->templates->addFolder(strtolower($this->context . '.' . $this->module), $viewsPath);
        }
    }

    protected function loadAclRules(): void
    {
        $className = "{$this->namespace}\Application\Acl\{$this->module}Acl";

        if (!class_exists($className)) {
            return;
        }

        $register = new $className($this->app->acl, $this->resource());
        $register->register();
    }

    protected function loadCommands(): void
    {
        $commandsPath = $this->modulePath('/UI/Console/commands.php');

        if (!file_exists($commandsPath)) {
            return;
        }

        $commands = include_once $commandsPath;

        array_map(function ($command) {
            $this->app->console->add($this->app->container->get($command));
        }, $commands);
    }

    protected function modulePath(string $path): string
    {
        return $this->modulePath . $path;
    }

    protected function resource(): string
    {
        return strtolower("{$this->context}_{$this->module}");
    }
}
