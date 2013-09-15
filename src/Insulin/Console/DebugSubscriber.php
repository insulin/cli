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
        return array();
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
}
