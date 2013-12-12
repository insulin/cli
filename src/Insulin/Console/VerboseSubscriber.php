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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VerboseSubscriber implements EventSubscriberInterface
{
    /**
     * @var OutputInterface we are going to log to the output of the App.
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::BOOT_SUCCESS => array(
                array('onKernelBootSuccess'),
            ),
            KernelEvents::BOOT_FAILURE => array(
                array('onKernelBootFailure'),
            ),
        );
    }

    /**
     * The Debug Subscriber will show debug messages based on events that are
     * being triggered by the core application or kernel.
     *
     * @param OutputInterface $output
     *   The output where we are going to debug to.
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Listen for Kernel last successful reached level.
     *
     * @param KernelBootEvent $event
     *   The event that contains the last successful reached level.
     */
    public function onKernelBootSuccess(KernelBootEvent $event)
    {
        $this->output->writeln(
            sprintf(
                "<info>Successfully reached level '%d'.</info>",
                $event->getKernel()->getBootedLevel()
            )
        );
    }

    /**
     * Listen for possible Kernel boot failures.
     *
     * @param KernelBootEvent $event
     *   The event that contains the boot failure.
     */
    public function onKernelBootFailure(KernelBootEvent $event)
    {
        $this->output->writeln(
            sprintf(
                "<error>Unable to boot due to: %s</error>",
                $event->getException()->getMessage()
            )
        );
    }
}
