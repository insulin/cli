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

namespace Insulin\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * An Insulin application is based on Symfony's Console Application.
 *
 * @api
 */
class Application extends BaseApplication
{
    /**
     * @var KernelInterface This applications' kernel.
     */
    private $kernel;

    public static $logo = <<<EOF
     ______                           ___
    /\__  _\                         /\_ \    __
    \/_/\ \/     ___     ____  __  __\//\ \  /\_\    ___
       \ \ \   /' _ `\  /',__\/\ \/\ \ \ \ \ \/\ \ /' _ `\
        \_\ \__/\ \/\ \/\__, `\ \ \_\ \ \_\ \_\ \ \/\ \/\ \
        /\_____\ \_\ \_\/\____/\ \____/ /\____\\\ \_\ \_\ \_\
        \/_____/\/_/\/_/\/___/  \/___/  \/____/ \/_/\/_/\/_/

EOF;


    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
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
     * This overrides the parent default commands to allow debug, shell and
     * path options.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     *   An InputDefinition instance.
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(
            new InputOption('--debug', '-d', InputOption::VALUE_NONE, 'Display timing and memory usage information.')
        );
        $definition->addOption(
            new InputOption('--shell', '-s', InputOption::VALUE_NONE, 'Launch the shell.')
        );
        $definition->addOption(
            new InputOption(
                '--process-isolation',
                null,
                InputOption::VALUE_NONE,
                'Launch commands from shell as a separate processes.'
            )
        );
        $definition->addOption(
            new InputOption(
                '--path',
                '-p',
                InputOption::VALUE_REQUIRED,
                'Path to SugarCRM instance root directory, defaults to current directory if none supplied.'
            )
        );

        return $definition;
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface $input
     *   An Input instance.
     * @param OutputInterface $output
     *   An Output instance.
     *
     * @return int
     *   Returns 0 if everything went fine, or an error code.
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->kernel->initialize();
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->kernel->get('dispatcher')->addSubscriber(new VerboseSubscriber($output));
        }
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $this->kernel->get('dispatcher')->addSubscriber(new DebugSubscriber($output));
        }

        $this->kernel->setSugarPath($input->getParameterOption(array('--path', '-p'), null));

        $this->registerCommands();

        if (true === $input->hasParameterOption(array('--shell', '-s'))) {
            $shell = new Shell($this);
            $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
            $shell->run();

            return 0;
        }

        $result = parent::doRun($input, $output);

        if ($this->kernel->isDebug()) {
            $output->writeln(
                sprintf(
                    '<info>Memory usage: %.2fMB (peak: %.2fMB), time: %.2fs</info>',
                    memory_get_usage() / 1024 / 1024,
                    memory_get_peak_usage() / 1024 / 1024,
                    microtime(true) - $this->kernel->getStartTime()
                )
            );
        }

        return $result;
    }

    /**
     * Gets the Insulin namespace.
     *
     * @return string
     *   The Insulin namespace.
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
     * - Commands are in the 'Command' sub-directory
     * - Commands extend Symfony\Component\Console\Command\Command
     *
     * @throws \RuntimeException if no search path for commands is available.
     */
    protected function registerCommands()
    {
        $this->kernel->boot();

        $searchPath = array();

        if (Kernel::BOOT_INSULIN <= $this->kernel->getBootedLevel()) {
            $searchPath[] = $this->kernel->getRootDir() . '/Command';
            $searchPath[] = $this->kernel->getHomeDir() . '/Command';
        }
        if (Kernel::BOOT_SUGAR_ROOT <= $this->kernel->getBootedLevel()) {
            // TODO give support to commands on SugarCRM instance
            // $searchPath[] = $this->kernel->get('sugar')->getPath() . '/custom/Insulin';
        }

        $searchPath = array_filter($searchPath, 'is_dir');

        if (empty($searchPath)) {
            throw new \RuntimeException(
                sprintf(
                    'No search path for commands available for run level "%s".',
                    $this->kernel->getBootedLevel()
                )
            );
        }

        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($searchPath);

        $prefix = $this->getNamespace() . '\\Command';
        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($finder as $file) {
            $ns = $prefix;
            if ($relativePath = $file->getRelativePath()) {
                $ns .= '\\' . strtr($relativePath, '/', '\\');
            }
            $r = new \ReflectionClass($ns . '\\' . $file->getBasename('.php'));
            if ($r->isSubclassOf(
                'Symfony\\Component\\Console\\Command\\Command'
            ) && !$r->isAbstract()) {
                $this->add($r->newInstance());
            }
        }
    }

    public function getHelp()
    {
        return '<info>' . self::$logo . '</info>' . parent::getHelp();
    }
}
