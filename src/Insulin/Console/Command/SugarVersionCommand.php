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

/**
 * This command provides information about SugarCRM version.
 *
 * You will be able to know which version, flavor and build number your Sugar
 * instance is running.
 *
 * @fixme Insulin commands need to extend from InsulinCommand so it requires an Insulin Application
 *
 * @api
 */
class SugarVersionCommand extends Command
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $kernel \Insulin\Console\KernelInterface */
        $kernel = $this->getApplication()->getKernel();

        if ($kernel->boot() < $kernel::BOOT_SUGAR_ROOT) {
            // FIXME change this to a common exception to be used by all commands
            throw new \Exception('Cannot execute command, no SugarCRM instance found.');
        }

        $sugar = $kernel->get('sugar');

        $flavor = $sugar->getInfo('flavor');
        $version = $sugar->getInfo('version');
        $build = $sugar->getInfo('build');

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
