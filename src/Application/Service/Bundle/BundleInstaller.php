<?php

namespace Cordo\Core\Application\Service\Bundle;

use SplFileInfo;
use FilesystemIterator;
use Doctrine\ORM\ORMSetup;
use RecursiveIteratorIterator;
use Cordo\Core\Application\App;
use Doctrine\ORM\EntityManager;
use RecursiveDirectoryIterator;
use Doctrine\ORM\Tools\SchemaTool;

class BundleInstaller
{
    private string $sourcePath;

    private string $destPath;

    private string $bundleName;

    private string $bundleDestName;

    public function __construct(string $sourcePath, string $destPath, string $bundleName, string $bundleDestName = null)
    {
        $this->sourcePath = $sourcePath;
        $this->destPath = $destPath;
        $this->bundleName = $bundleName;
        $this->bundleDestName = $bundleDestName ?: $bundleName;
    }

    public function copyFiles(): void
    {
        if (file_exists($this->destPath)) {
            throw new BundleInstallerException("Bundle {$this->bundleDestName} already exists");
        }

        mkdir($this->destPath, 0755);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                mkdir($this->destPath . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            } else {
                copy($item, $this->destPath . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            }
        }

        if ($this->bundleName !== $this->bundleDestName) {
            $this->parseFiles();
        }
    }

    private function parseFiles(): void
    {
        $directory = new RecursiveDirectoryIterator($this->destPath, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            $this->parseFile($file);
            $this->renameFile($file);
        }
    }

    private function parseFile(SplFileInfo $file): void
    {
        $fileContent = str_replace(
            ["{$this->bundleName}\\", strtolower($this->bundleName)],
            [$this->bundleDestName . '\\', strtolower($this->bundleDestName)],
            (string) file_get_contents($file->getPathname())
        );

        file_put_contents($file->getPathname(), $fileContent);
    }

    private function renameFile(SplFileInfo $file): void
    {
        if (strpos($file->getFilename(), $this->bundleName)) {
            rename($file->getPathname(), str_replace($this->bundleName, $this->bundleDestName, $file->getPathname()));
        }
    }

    public function createSchema(...$domains)
    {
        $config = ORMSetup::createXMLMetadataConfiguration([
            "{$this->destPath}/Users/Infrastructure/Persistance/Doctrine/ORM/Metadata",
        ]);

        $em = EntityManager::create(App::getInstance()->db_config, $config);
        $em
            ->getConnection()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('uuid_binary_ordered_time', 'binary');

        $classes = [];
        foreach ($domains as $domain) {
            $classes[] = $em->getClassMetadata($domain);
        }

        (new SchemaTool($em))->createSchema($classes);
    }
}
