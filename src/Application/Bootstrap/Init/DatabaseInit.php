<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Init;

use Cordo\Core\Application\App;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class DatabaseInit
{
    private const PROXY_DIR = 'storage/cache/doctrine/proxies';

    private const METADATA_CACHE_DIR = 'storage/cache/doctrine/metadata';

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

        $connection = DriverManager::getConnection($dbParams);

        $entityManager = new EntityManager($connection, self::createConfig(self::getPaths()));
        $entityManager
            ->getConnection()
            ->getDatabasePlatform();

        return $entityManager;
    }

    /**
     * @param string[] $paths
     */
    public static function createConfig(array $paths): Configuration
    {
        $config = ORMSetup::createXMLMetadataConfiguration(
            $paths,
            App::isDevelopment(),
            App::rootPath(self::PROXY_DIR),
            self::createCache()
        );
        // metadata and query caches are shared, results must not end up in the file based one
        $config->setResultCache(new ArrayAdapter());
        $config->setAutoGenerateProxyClasses(App::isDevelopment());

        return $config;
    }

    /**
     * Must always be passed to ORMSetup explicitly - left to itself it picks an adapter
     * by scanning loaded extensions and connects to a local Redis or Memcached.
     */
    private static function createCache(): CacheItemPoolInterface
    {
        if (App::isDevelopment()) {
            return new ArrayAdapter();
        }

        return new PhpFilesAdapter('doctrine', 0, App::rootPath(self::METADATA_CACHE_DIR));
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

        if (App::config()->get('app.oauth') === true) {
            $path = dirname(__FILE__) . '/../../../Infractructure/Persistance/Doctrine/ORM/Metadata/OAuth';
            if (file_exists($path)) {
                $paths[] = realpath($path);
            }
        }

        return $paths;
    }

    private static function getDefinitions(): array
    {
        return [
            'entity_manager' => \DI\factory(static fn() => DatabaseInit::initDB()),
            'db_config' => \DI\factory(static fn() => DatabaseInit::getDbParams()),
            'connection' => \DI\factory(static function (ContainerInterface $c) {
                return $c->get('entity_manager')->getConnection();
            }),
            EntityManager::class => \DI\get('entity_manager'),
            Connection::class => \DI\get('connection'),
        ];
    }
}
