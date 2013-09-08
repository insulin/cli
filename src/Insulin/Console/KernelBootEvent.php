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

use Symfony\Component\EventDispatcher\Event;

/**
 * KernelBootEvent contains the Kernel Boot event data.
 *
 * @see KernelEvents for a list of possible event names that trigger this
 *   event.
 * @see Kernel::boot to see how this event is triggered.
 *
 * @api
 */
class KernelBootEvent extends Event
{
    /**
     * The Boot level that triggered this event.
     *
     * @var int
     */
    protected $level;

    /**
     * The error message from the exception when a failure happens.
     *
     * @var string
     */
    protected $error;

    /**
     * Creates a new instance with the boot level and the error message if an
     * error occurred.
     *
     * @param int $level
     *   The Kernel's boot level.
     * @param string $error
     *   The Kernel's error message if failure (defaults to empty).
     */
    public function __construct($level, $error = '')
    {
        $this->level = $level;
        $this->error = $error;
    }

    /**
     * Gets the Kernel's boot level that triggered this event.
     *
     * @return int
     *   The Kernel's boot level defined on this event.
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Gets the error message explaining why the Kernel failed to boot.
     *
     * @return int
     *   The Kernel's error message if boot failed.
     */
    public function getError()
    {
        return $this->error;
    }
}
