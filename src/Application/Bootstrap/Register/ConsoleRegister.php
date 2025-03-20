<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Register;

use Cordo\Core\Application\App;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\DebugFormatterHelper;

class ConsoleRegister
{
    public function __construct(private App $app)
    {
    }

    public function register(): void
    {
        $helpers = [];
        $helpers[] = new FormatterHelper();
        $helpers[] = new DebugFormatterHelper();
        $helpers[] = new ProcessHelper();
        $helpers[] = new QuestionHelper();
        
        $application = new Application();
        $application->setHelperSet(new HelperSet($helpers));

        $this->app->container->set('console', $application);
    }
}
