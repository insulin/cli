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

use Insulin\Sugar\Finder;
use Insulin\Sugar\Sugar;
use Insulin\Sugar\SugarManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BootSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::BOOT_LEVEL => array(
                array('onKernelBootLevel'),
            ),
        );
    }

    /**
     * Listen for Kernel boot level.
     *
     * @param KernelBootLevelEvent $event
     *   The event that contains the level to boot to.
     *
     */
    public function onKernelBootLevel(KernelBootLevelEvent $event)
    {
        static $levels = array(
            KernelInterface::BOOT_SUGAR_ROOT => 'bootSugarRoot',
            KernelInterface::BOOT_SUGAR_CONFIGURATION => 'bootSugarConfiguration',
            KernelInterface::BOOT_SUGAR_DATABASE => 'bootSugarDatabase',
            KernelInterface::BOOT_SUGAR_FULL => 'bootSugarFull',
            KernelInterface::BOOT_SUGAR_LOGIN => 'bootSugarLogin',
        );

        if (isset($levels[$event->getLevel()])) {
            call_user_func(
                array($this, $levels[$event->getLevel()]),
                $event
            );
        }
    }

    /**
     * Boot Sugar root.
     *
     * @param KernelBootLevelEvent $event
     *   The event that triggered this boot level process.
     */
    protected function bootSugarRoot(KernelBootLevelEvent $event)
    {
        $kernel = $event->getKernel();
        /* @var $manager \Insulin\Sugar\Manager */
        $manager = $kernel->get('sugar_manager');

        $path = $kernel->getSugarPath();
        if (!empty($path)) {
            $sugar = $manager->get($path);
        } else {
            $sugar = $manager->find($kernel->getCwd());
        }

        $sugar->bootRoot();
        $kernel->getContainer()->set('sugar', $sugar);
    }

    /**
     * Boot Sugar configuration.
     *
     * @param KernelBootLevelEvent $event
     *   The event that triggered this boot level process.
     */
    protected function bootSugarConfiguration(KernelBootLevelEvent $event)
    {
        $event->getKernel()->get('sugar')->bootConfig();

        // FIXME: this needs to be improved
        $e = new Event();
        $e->message = '<info>Loaded configurations.</info>';
        $event->getDispatcher()->dispatch('debug', $e);
    }

    /**
     * Boot Sugar database.
     *
     * @param KernelBootLevelEvent $event
     *   The event that triggered this boot level process.
     *
     * @throws \RuntimeException
     *   If database driver found on this SugarCRM instance isn't supported.
     */
    protected function bootSugarDatabase(KernelBootLevelEvent $event)
    {
        $event->getKernel()->get('sugar')->bootDatabase();

        // FIXME: this needs to be improved
        $e = new Event();
        $e->message = '<info>Connected to database.</info>';
        $event->getDispatcher()->dispatch('debug', $e);
    }

    /**
     * Boot Sugar full.
     *
     * @param KernelBootLevelEvent $event
     *   The event that triggered this boot level process.
     */
    protected function bootSugarFull(KernelBootLevelEvent $event)
    {
        $event->getKernel()->get('sugar')->bootApplication();
    }

    /**
     * Boot Sugar login.
     *
     * @param KernelBootLevelEvent $event
     *   The event that triggered this boot level process.
     *
     * @throws \RuntimeException
     *   If there's no user with administrator privileges on this SugarCRM
     *   instance.
     */
    protected function bootSugarLogin(KernelBootLevelEvent $event)
    {
        // FIXME: add support for user being supplied by --user|-u global option

        $event->getKernel()->get('sugar')->localLogin();
    }
}
