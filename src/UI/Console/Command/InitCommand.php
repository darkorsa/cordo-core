<?php

namespace Cordo\Core\UI\Console\Command;

use Cordo\Core\Application\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'core:init')]
class InitCommand extends BaseConsoleCommand
{
    protected $output;

    protected function configure()
    {
        $this
            ->setDescription('Initialize app')
            ->setHelp('Creates neccessary db tables and functions')
            ->addOption(
                'withMysqlUuid',
                null,
                InputOption::VALUE_NONE,
                'Create Uuid DB helper functions'
            )
            ->addOption(
                'withPgsqlUuid',
                null,
                InputOption::VALUE_NONE,
                'Create Uuid DB helper functions'
            )
            ->addOption(
                'withOAuth',
                null,
                InputOption::VALUE_NONE,
                'Create OAuth DB tables'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->container->get('entity_manager');

        $command = $this->getApplication()->find('dbal:run-sql');

        if ($input->getOption('withOAuth')) {
            $this->importOAuth($command, $output);
        }

        if ($input->getOption('withMysqlUuid')) {
            $this->importUuidMysql($command, $output);
        }

        if ($input->getOption('withPgsqlUuid')) {
            $this->importUuidPgsql($command, $output);
        }

        return 0;
    }

    private function importOAuth(Command $command, $output)
    {
        $arguments = [
            'sql' => file_get_contents(App::rootPath('resources/database/sql/oauth.sql')),
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }

    private function importUuidMysql(Command $command, $output)
    {
        $arguments = [
            'sql' => file_get_contents(App::rootPath('resources/database/sql/uuid.sql')),
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }

    private function importUuidPgsql(Command $command, $output)
    {
        $arguments = [
            'sql' => file_get_contents(App::rootPath('resources/database/sql/uuid-pgsql.sql')),
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
