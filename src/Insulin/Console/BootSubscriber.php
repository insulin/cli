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
        if ($event->getLevel() === KernelInterface::BOOT_SUGAR_ROOT) {
            $this->bootSugarRoot($event);
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

        $sugar->init();
        $kernel->getContainer()->set('sugar', $sugar);
    }
}
