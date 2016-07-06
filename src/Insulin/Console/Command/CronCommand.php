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

use Insulin\Sugar\Exception\RuntimeException;
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
class CronCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cron:run')
            ->setDefinition(array(
                new InputArgument('jobId', InputArgument::IS_ARRAY, 'The specific scheduler job id to run.'),
                new InputOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force the cron job run.', false),
            ))
            ->setDescription('Run all active cron jobs or all jobs given.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command runs all active cron jobs:

  <info>%command.full_name%</info>

You can also run only some specific job or list of jobs by providing the ids:

  <info>%command.full_name% 7542f4f8-7d15-dd03-d6a1-52af6cfee427 804d64aa-f22f-c185-48c1-52af6c580622</info>

It's also possible to force the cron to run (useful to ignore status and last run time):
  <info>%command.full_name% --force</info>
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

        if ($kernel->getBootedLevel() < $kernel::BOOT_SUGAR_FULL) {
            // FIXME change this to a common exception to be used by all commands
            throw new \Exception('Cannot execute command, no valid SugarCRM instance found.');
        }

        $sugar = $kernel->get('sugar');

        // TODO move this to Sugar proxy to abstract per version (this is only for 7.x with Job Queue changes
        $jobIds = $input->getArgument('jobId');

        require_once 'modules/Schedulers/Scheduler.php';
        $s = new \Scheduler();
        $jobs = $s->get_full_list('', "schedulers.status='Active'");

        if (!empty($jobIds)) {
            $jobs = array_filter($jobs, function ($job) use ($jobIds) {
                return in_array($job->id, $jobIds);
            });
        }

        if (empty($jobs)) {
            throw new RuntimeException('No Scheduler jobs to run.');
        }

        foreach ($jobs as $focus) {
            if ($input->getOption('force') || $focus->fireQualified()) {
                $job = $focus->createJob();
                if (!$job->runJob()) {
                    // TODO provide better exceptions for failed jobs
                    throw new \RuntimeException(
                        sprintf("Cron failed for job id '%s'.", $focus->id)
                    );
                }
                $output->writeln(
                    sprintf("Job '%s' ran successful.", $job->scheduler_id)
                );
            }
        }

        $output->writeln('Cron run successfully.');
    }
}
