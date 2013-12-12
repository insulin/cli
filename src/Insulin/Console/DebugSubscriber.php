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

class DebugSubscriber implements EventSubscriberInterface
{
    /**
     * @var OutputInterface we are going to log to the output of the App.
     */
    protected $output;

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::BOOT_LEVEL_BEFORE => array(
                array('onKernelBootLevelBefore'),
            ),
            KernelEvents::BOOT_LEVEL_SUCCESS => array(
                array('onKernelBootLevelSuccess'),
            ),
            KernelEvents::BOOT_LEVEL_FAILURE => array(
                array('onKernelBootLevelFailure'),
            ),
            // FIXME: this needs to be improved
            'debug' => array('debug'),
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
     * Listen for Kernel boot success levels.
     *
     * @param KernelBootLevelEvent $event
     *   The event that contains the level that we're trying to boot to.
     */
    public function onKernelBootLevelBefore(KernelBootLevelEvent $event)
    {
        $this->output->writeln(
            sprintf("<info>Attempting to reach level '%d'.</info>", $event->getLevel())
        );
    }

    /**
     * Listen for Kernel boot success levels.
     *
     * @param KernelBootLevelEvent $event
     *   The event that contains the level that was booted successfully.
     */
    public function onKernelBootLevelSuccess(KernelBootLevelEvent $event)
    {
        if ($event->getLevel() === Kernel::BOOT_SUGAR_LOGIN) {
            $this->output->writeln(
                sprintf("<info>Logged in as '%s'.</info>", $GLOBALS['current_user']->user_name)
            );
        }

        $this->output->writeln(
            sprintf("<info>Reached level '%d'.</info>", $event->getLevel())
        );
    }

    /**
     * Listen for possible Kernel boot failures.
     *
     * @param KernelBootEvent $event
     *   The event that contains the boot failure.
     */
    public function onKernelBootLevelFailure(KernelBootLevelEvent $event)
    {
        $this->output->writeln(
            sprintf(
                "<error>Unable to reach level '%d' due to: %s</error>",
                $event->getLevel(),
                $event->getException()->getMessage()
            )
        );
    }

    /**
     * FIXME: this needs to be improved
     */
    public function debug($event)
    {
        $this->output->writeln($event->message);
    }
}
