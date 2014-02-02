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
 * This Event is triggered by the Kernel while booting to a certain level.
 *
 * @see KernelEvents for a list of possible event names that can trigger this
 *   event.
 * @see Kernel::boot to see how this event is triggered.
 *
 * @api
 */
class KernelBootLevelEvent extends Event
{
    /**
     * The Boot level that triggered this event.
     *
     * @var int
     */
    protected $level;

    /**
     * The Kernel instance that triggered this event.
     *
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * The Exception instance when there is a boot level failure.
     *
     * @var \Exception
     */
    protected $exception;

    /**
     * Creates a new instance with the boot level and the error message if an
     * error occurred.
     *
     * @param int $level
     *   The Kernel's boot level.
     * @param KernelInterface $kernel
     *   The Kernel's.
     * @param \Exception $exception
     *   The Exception instance that is thrown when there is a boot level
     *   failure.
     */
    public function __construct($level, KernelInterface $kernel, \Exception $exception = null)
    {
        $this->level = $level;
        $this->kernel = $kernel;
        $this->exception = $exception;
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
     * Gets the Kernel instance that triggered this event.
     *
     * @return KernelInterface
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Gets the exception instance that is thrown when there is a boot level
     * failure.
     *
     * @return \Exception
     *   The Exception instance or null if no failure occurred.
     */
    public function getException()
    {
        return $this->exception;
    }
}
