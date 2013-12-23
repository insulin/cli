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
 * @fixme Insulin commands need to extend from InsulinCommand so it requires an Insulin Application
 *
 * @api
 */
class RepairRebuildCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('repair-rebuild')
            ->setAliases(array('rr'))
            ->setDefinition(array(
                new InputArgument('module', InputArgument::IS_ARRAY, 'The specific module name to repair and rebuild.'),
                new InputOption('database-schema-changes', null, InputOption::VALUE_OPTIONAL, 'How database schema changes should be handled.', 'print'),
            ))
            ->setDescription('Repair and rebuild modules related structures.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> performs the following generalized steps:

* Clears cache so that files are rebuilt.
* Goes through custom/Extensions and rebuilds all layoutdefs, vardefs and language files.
* Ensure list of audited fields is correct.
* Compares database schema against vardefs.

  <info>%command.full_name%</info>

By default, if any database schema changes are detected they are printed on screen.
You can change the way how the changes are handled by using the <comment>--database-schema-changes</comment> option:

  <info>%command.full_name% --database-schema-changes=print</info>

Available handlers allow you to apply <comment>all</comment> database schema changes at once, <comment>print</comment> them on screen and review them all by running a <comment>step</comment> by <comment>step</comment> process.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $input->getArgument('module');
        $noInteraction = $input->getOption('no-interaction');
        $databaseSchemaChanges = $input->getOption('database-schema-changes');

        if ($noInteraction && $databaseSchemaChanges === 'step') {
            $databaseSchemaChanges = 'print';
        }
    }
}
