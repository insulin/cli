<?php

/*
 * This file is part of the Insulin CLI
 *
 * Copyright (c) 2008-2012 Filipe Guerra, João Morais
 * http://cli.sugarmeetsinsulin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Insulin\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class Application extends BaseApplication
{
    protected $reflected;

    private $kernel;

    /**
     * Constructor.
     *
     * @param Kernel $kernel
     *   A Kernel instance
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct($kernel->getName(), $kernel->getVersion());
    }

    /**
     * Gets the default input definition.
     *
     * This overrides the parent default commands to allow a debug and pipe option.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(
            new InputOption('--debug', '-d', InputOption::VALUE_NONE, 'Switches on debug mode.')
        );
        $definition->addOption(
            new InputOption('--shell', '-s', InputOption::VALUE_NONE, 'Launch the shell.')
        );
        $definition->addOption(
            new InputOption('--process-isolation', null, InputOption::VALUE_NONE, 'Launch commands from shell as a separate processes.')
        );
        $definition->addOption(
            new InputOption('--root', '-r', InputOption::VALUE_REQUIRED, 'Sugar root directory, defaults to current directory.')
        );

        return $definition;
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->kernel->setSugarRoot($input->getParameterOption(array('--root', '-r'), null));

        $this->registerCommands();

        if (true === $input->hasParameterOption(array('--shell', '-s'))) {
            $shell = new Shell($this);
            $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
            $shell->run();

            return 0;
        }

        return parent::doRun($input, $output);
    }

    /**
     * Gets the Insulin namespace.
     *
     * @return string
     *   The Insulin namespace
     *
     * @api
     */
    public function getNamespace()
    {
        return 'Insulin\Console';
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * @return KernelInterface
     *   A KernelInterface instance.
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Registers Commands based on the boot level reached on the Kernel.
     *
     * Insulin commands follow the conventions:
     *
     * * Commands are in the 'Command' sub-directory
     * * Commands extend Symfony\Component\Console\Command\Command
     */
    protected function registerCommands()
    {
        $level = $this->kernel->boot();

        $searchPath = array();

        if (Kernel::BOOT_INSULIN <= $level) {

            if ($dir = realpath($this->kernel->getRootDir() . '/Command')) {
                $searchPath[] = $dir;
            }
            // TODO give support for commands on home path
            // searchPath[] = '~/.insulin/Command';
        }
        if (Kernel::BOOT_SUGAR_ROOT <= $level) {
            // TODO give support to commands on Sugar instance
            // searchPath[] = $this->kernel->getSugarRoot() . 'custom/Insulin';
        }

        // TODO make the other phase commands retrieval

        if (empty($searchPath)) {
            throw new \RuntimeException(sprintf(
                'No search path for commands available for run level "%s".',
                $level
            ));
        }

        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($searchPath);

        $prefix = $this->getNamespace() . '\\Command';
        foreach ($finder as $file) {
            $ns = $prefix;
            if ($relativePath = $file->getRelativePath()) {
                $ns .= '\\' . strtr($relativePath, '/', '\\');
            }
            $r = new \ReflectionClass($ns . '\\' . $file->getBasename('.php'));
            if ($r->isSubclassOf(
                'Symfony\\Component\\Console\\Command\\Command'
            ) && !$r->isAbstract()
            ) {
                $this->add($r->newInstance());
            }
        }
    }
}
