<?php

/*
 * This file is part of the Insulin CLI
 *
 * Copyright (c) 2008-2013 Filipe Guerra, João Morais
 * http://cli.sugarmeetsinsulin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Insulin\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// FIXME Insulin commands need to extend from InsulinCommand so it requires an Insulin Application
class SugarVersionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('sugar:version')
            ->setDescription('Print SugarCRM flavor, version and build number.')
            ->setHelp(
                <<<EOF
The <info>sugar:version</info> command shows the current SugarCRM flavor, version and build number.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getApplication()->getKernel();

        $flavor = $kernel->getSugarInfo('flavor');
        $version = $kernel->getSugarInfo('version');
        $build = $kernel->getSugarInfo('build');

        /*
        if ($input->getOption('pipe')) {
            $output->writeln(
                '"' . implode('","', array($flavor, $version, $build)) . '"'
            );

            return;
        }
        */


        // TODO translations
        /*
        $translator = $this->getContainer()->get('translator');
        $text = $translator->trans(
            "Sugar %flavor% %version% build %build%",
            array(
                '%flavor%' => $flavor,
                '%version%' => $version,
                '%build%' => $build
            )
        );
        */
        $text = sprintf(
            "SugarCRM %s %s build %s",
            $flavor,
            $version,
            $build
        );

        $output->writeln($text);
    }
}
