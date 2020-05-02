<?php

namespace Cordo\Core\UI\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

class InitCommand extends BaseConsoleCommand
{
    protected static $defaultName = 'core:init';

    protected $output;

    protected function configure()
    {
        $this
            ->setDescription('Initialize app')
            ->setHelp('Creates neccessary db tables and functions')
            ->addOption(
                'withUuid',
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

        $helperSet = new HelperSet(array(
            'db' => new ConnectionHelper($em->getConnection()),
            'em' => new EntityManagerHelper($em)
        ));

        $command = $this->getApplication()->find('dbal:import');
        $command->setHelperSet($helperSet);

        if ($input->getOption('withOAuth')) {
            $this->importOauthSql($command, $output);
        }

        if ($input->getOption('withUuid')) {
            $this->importUuidSql($command, $output);
        }

        return 0;
    }

    private function importOauthSql(Command $command, $output)
    {
        $arguments = [
            'command' => 'dbal:import',
            'file'    => resources_path() . 'database/sql/oauth.sql',
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }

    private function importUuidSql(Command $command, $output)
    {
        $arguments = [
            'command' => 'dbal:import',
            'file'    => resources_path() . 'database/sql/uuid.sql',
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
