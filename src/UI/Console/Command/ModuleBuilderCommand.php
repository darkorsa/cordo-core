<?php

namespace Cordo\Core\UI\Console\Command;

use ZipArchive;
use FilesystemIterator;
use RecursiveIteratorIterator;
use Cordo\Core\Application\App;
use RecursiveDirectoryIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'core:module-builder')]
class ModuleBuilderCommand extends Command
{
    private const DEFAULT_ARCHIVE = 'CRUDModule.zip';

    protected $output;

    protected function configure()
    {
        $this
            ->setDescription('Builds a new app module.')
            ->setHelp('Creates file and dir default structure for a new module.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('context', InputArgument::REQUIRED),
                    new InputArgument('module_name', InputArgument::REQUIRED),
                    new InputArgument('module_archive', InputArgument::OPTIONAL),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $params = (object) $input->getArguments();

        $context = $params->context;
        $moduleName = $params->module_name;
        $moduleArchive = $params->module_archive ?: self::DEFAULT_ARCHIVE;
        $resourcePath = App::rootPath("resources/module/{$moduleArchive}");

        if (!file_exists($resourcePath)) {
            $output->writeln("<error>Cannot find archive in location: {$resourcePath}</error>");
            exit;
        }

        if (file_exists(App::rootPath("app/{$context}/{$moduleName}"))) {
            $output->writeln("<error>Module {$moduleName} already exists</error>");
            exit;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "Should I proceed with creating module {$moduleName} in {$context} context? (y/n) ",
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Bye!');
            exit;
        }

        $this->buildModule($context, $moduleName, $resourcePath);

        return 0;
    }

    protected function buildModule(string $context, string $moduleName, string $resourcePath): void
    {
        $contextPath = App::rootPath("app/{$context}");
        $modulePath = "{$contextPath}/{$moduleName}";

        $this->createContextDir($contextPath);

        $this->createModuleDir($modulePath);

        $this->extractArchive($resourcePath, $modulePath);

        $this->renameFiles($context, $modulePath, $moduleName);

        $this->parseFiles($context, $modulePath, $moduleName);

        $this->output->writeln('Successfully done!');
    }

    protected function createContextDir(string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        $this->output->writeln('Creating context folder...');
        mkdir($path, 0777, true);
    }

    protected function createModuleDir(string $path): void
    {
        $this->output->writeln('Creating module folder...');
        mkdir($path, 0777, true);
    }

    protected function extractArchive(string $resourcePath, string $modulePath): void
    {
        $this->output->writeln('Extracting archive...');

        $zip = new ZipArchive();
        if ($zip->open($resourcePath) === true) {
            $zip->extractTo($modulePath);
            $zip->close();
            $this->output->writeln('Extraction complete');
        } else {
            $this->output->writeln('<error>Could not extract archive from path: ' . $resourcePath . '</error>');
        }
    }

    protected function renameFiles(string $context, string $modulePath, string $moduleName): void
    {
        $this->output->writeln('Renaming files...');

        $directory = new RecursiveDirectoryIterator($modulePath, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            $replacements = $this->getReplacements($context, $moduleName);

            $renamed = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $file->getPathname()
            );

            rename($file->getPathname(), $renamed);
        }
    }

    protected function parseFiles(string $context, string $modulePath, string $moduleName): void
    {
        $this->output->writeln('Parsing files...');

        $directory = new RecursiveDirectoryIterator($modulePath, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            $replacements = $this->getReplacements($context, $moduleName);

            $fileContent = str_replace(
                array_keys($replacements),
                array_values($replacements),
                (string) file_get_contents($file->getPathname())
            );

            file_put_contents($file->getPathname(), $fileContent);
        }
    }

    protected function getReplacements(string $context, string $moduleName): array
    {
        return [
            '[Context]' => $context,
            '[context]' => strtolower($context),
            '[entity]' => strtolower($this->getSingular($moduleName)),
            '[entities]' => strtolower($moduleName),
            '[Entity]' => $this->getSingular($moduleName),
            '[Entities]' => $moduleName,
        ];
    }

    protected function getSingular(string $moduleName): string
    {
        return substr($moduleName, -1) === 's' ? substr($moduleName, 0, -1) : $moduleName;
    }
}
