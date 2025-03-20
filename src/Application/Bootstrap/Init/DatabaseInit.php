<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Init;

use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Cordo\Core\Application\App;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;
use Ramsey\Uuid\Doctrine\UuidType;
use Psr\Container\ContainerInterface;

class DatabaseInit
{
    public static function init(): array
    {
        return self::getDefinitions();
    }

    public static function initDB(): EntityManager
    {
        $dbParams = self::getDbParams();

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $config = ORMSetup::createXMLMetadataConfiguration(
            self::getPaths(),
            App::isDevelopment(),
            App::rootPath('cache/doctrine/')
        );
        $config->setAutoGenerateProxyClasses(App::isDevelopment());

        $connection = DriverManager::getConnection( $dbParams);

        $entityManager = new EntityManager($connection, $config);
        $entityManager
            ->getConnection()
            ->getDatabasePlatform();

        return $entityManager;
    }

    public static function getDbParams(): array
    {
        return App::config()->get('db.drivers')[App::config()->get('db.driver')];
    }

    private static function getPaths(): array
    {
        $paths = [];
        foreach (App::config()->get('app.modules') as $module) {
            $reflection = new \ReflectionClass($module);
            $path = dirname($reflection->getFileName()) . '/Infrastructure/Persistance/Doctrine/ORM/Metadata';
            if (file_exists($path)) {
                $paths[] = realpath($path);
            }
        }

        return $paths;
    }

    private static function getDefinitions(): array
    {
        return [
            'entity_manager' => \DI\factory(static fn () => DatabaseInit::initDB()),
            'db_config' => \DI\factory(static fn () => DatabaseInit::getDbParams()),
            'connection' => \DI\factory(static function (ContainerInterface $c) {
                return $c->get('entity_manager')->getConnection();
            }),
            EntityManager::class => \DI\get('entity_manager'),
            Connection::class => \DI\get('connection'),
        ];
    }
}
