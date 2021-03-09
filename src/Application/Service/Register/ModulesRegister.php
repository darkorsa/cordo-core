<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Service\Register;

use DI\Container;
use Noodlehaus\Config;
use League\Plates\Engine;
use Cordo\Core\UI\Http\Router;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Laminas\Permissions\Acl\Acl;
use League\Event\EmitterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Translation\Translator;
use Cordo\Core\Application\Config\ModuleParser;

class ModulesRegister
{
    protected static $register = [];

    public static function add(string $module): void
    {
        static::$register[] = $module;
    }

    public static function registerRoutes(Router $router, ContainerInterface $container): void
    {
        $registerRoutes = function (string $context, string $module) use ($router, $container): void {
            $className = self::routesClassname($context, $module);

            if (!class_exists($className)) {
                return;
            }

            $routesRegister = new $className($router, $container, self::resource($context, $module));
            $routesRegister->register();
        };

        self::call($registerRoutes);
    }

    public static function registerCommands(Application $application, ContainerInterface $container): void
    {
        $registerCommands = function (
            string $context,
            string $module
        ) use (
            $application,
            $container
        ): void {
            $commandsPath = self::commandsPath($context, $module);

            if (!file_exists($commandsPath)) {
                return;
            }

            $commands = include_once $commandsPath;

            array_map(static function ($command) use ($application, $container) {
                $application->add($container->get($command));
            }, $commands);
        };

        self::call($registerCommands);
    }

    public static function registerDefinitions(): array
    {
        $getDefinitions = function (string $context, string $module): array {
            $definitionsPath = self::definitionsPath($context, $module);

            if (file_exists($definitionsPath)) {
                return include_once $definitionsPath;
            }

            return [];
        };

        $definitions = include_once root_path() . 'bootstrap/definitions.php';

        // app definitions
        foreach (static::$register as $moduleInContext) {
            [$context, $module] = explode('\\', $moduleInContext);
            $definitions = array_merge($definitions, $getDefinitions($context, $module));
        }

        return $definitions;
    }

    public static function registerConfigs(Config $config): void
    {
        $registerConfigs = function (string $context, string $module) use ($config): void {
            $configsPath = self::configsPath($context, $module);

            if (file_exists($configsPath)) {
                $moduleConfig = new Config(
                    $configsPath,
                    new ModuleParser(strtolower($context), strtolower($module))
                );
                $config->merge($moduleConfig);
            }
        };

        self::call($registerConfigs);
    }

    public static function registerHandlersMap(): array
    {
        $getHandlers = function (string $context, string $module): array {
            $handlersMapPath = self::handlersMapPath($context, $module);

            if (file_exists($handlersMapPath)) {
                return include_once $handlersMapPath;
            }

            return [];
        };

        static $handlersMap = [];

        if (!empty($handlersMap)) {
            return $handlersMap;
        }

        // app handlers
        foreach (static::$register as $moduleInContext) {
            [$context, $module] = explode('\\', $moduleInContext);
            $handlersMap = array_merge($handlersMap, $getHandlers($context, $module));
        }

        return $handlersMap;
    }

    public static function registerListeners(EmitterInterface $emitter, ContainerInterface $container): void
    {
        $registerListeners = function (string $context, string $module) use ($emitter, $container): void {
            $className = self::listenerClassname($context, $module);

            if (!class_exists($className)) {
                return;
            }

            $eventsRegister = new $className($emitter, $container, strtolower($module));
            $eventsRegister->register();
        };

        self::call($registerListeners);
    }

    public static function registerEntities(): array
    {
        $paths = [];

        $registerEntities = function (string $context, string $module) use (&$paths): void {
            $entitiesPath = self::entitiesPath($context, $module);

            if (file_exists($entitiesPath)) {
                $paths[] = $entitiesPath;
            }
        };

        self::call($registerEntities);

        return $paths;
    }

    public static function registerAclData(Acl $acl): void
    {
        $registerAcl = function (string $context, string $module) use ($acl): void {
            $className = self::aclClassname($context, $module);

            if (!class_exists($className)) {
                return;
            }

            $aclRegister = new $className($acl, self::resource($context, $module));
            $aclRegister->register();
        };

        self::call($registerAcl);
    }

    public static function registerViews(Engine $templates): void
    {
        $registerViews = function (string $context, string $module) use ($templates): void {
            $viewsPath = self::viewsPath($context, $module);

            if (file_exists($viewsPath)) {
                $templates->addFolder(strtolower($context . '.' . $module), $viewsPath);
            }
        };

        self::call($registerViews);
    }

    public static function registerTranslations(Translator $translator, ContainerInterface $container): void
    {
        $config = $container->get('config');

        $registerTranslations = function (
            string $context,
            string $module
        ) use (
            $translator,
            $config
        ): void {
            $translationsPath = self::translationsPath($context, $module);

            if (file_exists($translationsPath)) {
                $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($translationsPath));

                foreach ($rii as $file) {
                    if ($file->isDir()) {
                        continue;
                    }

                    if (preg_match('/([a-z0-9]+)\.([a-z]{2})\.yaml/', $file->getPathname(), $matches)) {
                        $locale = $config->get('trans.locales')[$matches[2]] ?? null;
                        if (!$locale) {
                            continue;
                        }

                        $translator->addResource('yaml', $file->getPathname(), $locale, $matches[1]);
                    }
                }
            }
        };

        self::call($registerTranslations);
    }

    private static function call(callable $function)
    {
        // app
        foreach (static::$register as $moduleInContext) {
            [$context, $module] = explode('\\', $moduleInContext);

            $function($context, $module);
        }
    }

    private static function resource(string $context, string $module): string
    {
        return strtolower("{$context}\\{$module}");
    }

    private static function getModuleNamespace(string $context, string $module)
    {
        return "App\\{$context}\\{$module}";
    }

    private static function getModulePath(string $context, string $module)
    {
        return app_path($context . '/' . $module);
    }

    private static function routesClassname(string $context, string $module): string
    {
        return self::getModuleNamespace($context, $module) . "\UI\Http\Route\\{$module}Routes";
    }

    private static function commandsPath(string $context, string $module): string
    {
        return self::getModulePath($context, $module) . "/UI/Console/commands.php";
    }

    private static function definitionsPath(string $context, string $module): string
    {
        return self::getModulePath($context, $module) . "/Application/definitions.php";
    }

    protected static function configsPath(string $context, string $module): string
    {
        return self::getModulePath($context, $module) . "/Application/config";
    }

    private static function handlersMapPath(string $context, string $module): string
    {
        return self::getModulePath($context, $module) . "/Application/handlers.php";
    }

    private static function listenerClassname(string $context, string $module): string
    {
        return self::getModuleNamespace($context, $module) . "\Application\Event\Register\\{$module}Listeners";
    }

    private static function entitiesPath(string $context, string $module): string
    {
        return self::getModulePath($context, $module)
            . "/Infrastructure/Persistance/Doctrine/ORM/Metadata";
    }

    private static function aclClassname(string $context, string $module): string
    {
        return self::getModuleNamespace($context, $module) . "\Application\Acl\\{$module}Acl";
    }

    private static function viewsPath(string $context, string $module): string
    {
        return self::getModulePath($context, $module) . "/UI/views";
    }

    private static function translationsPath(string $context, string $module): string
    {
        return self::getModulePath($context, $module) . "/UI/trans";
    }
}
