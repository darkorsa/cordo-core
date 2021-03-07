<?php

namespace Cordo\Core\Application\Service\Bundle;

use SplFileInfo;
use App\Register;
use FilesystemIterator;
use RecursiveIteratorIterator;
use Nette\PhpGenerator\PhpFile;
use RecursiveDirectoryIterator;
use Nette\PhpGenerator\ClassType;
use Doctrine\ORM\Tools\SchemaTool;
use Nette\PhpGenerator\PsrPrinter;

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

    public function registerModules(string ...$modules)
    {
        // register
        foreach ($modules as $module) {
            Register::add($this->bundleDestName . '\\' . $module);
        }

        // add modules to app/Register file
        $class = ClassType::from(Register::class);
        $file = (new PhpFile())->setStrictTypes();

        $namespace = $file->addNamespace('App');
        $namespace->add($class);
        $namespace->addUse('Cordo\Core\Application\Service\Register\ModulesRegister');

        file_put_contents(app_path() . 'Register.php', (new PsrPrinter)->printFile($file));
    }

    public function createSchema(...$domains)
    {
        $em = require(root_path() . 'bootstrap/db.php');
        $tool = new SchemaTool($em);

        $classes = [];
        foreach ($domains as $domain) {
            $classes[] = $em->getClassMetadata($domain);
        }

        $tool->createSchema($classes);
    }
}
