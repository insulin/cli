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
 * This Event is triggered by the Kernel while doing a full boot.
 *
 * @see KernelEvents for a list of possible event names that can trigger this
 *   event.
 * @see Kernel::boot to see how this event is triggered.
 *
 * @api
 */
class KernelBootEvent extends Event
{
    /**
     * The Kernel instance that triggered this event.
     *
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * The Exception thrown when there is a full boot failure.
     *
     * @var \Exception
     */
    protected $exception;

    /**
     * Creates a new instance with the boot level and the error message if an
     * error occurred.
     *
     * @param KernelInterface $kernel
     *   The Kernel's.
     * @param \Exception $exception
     *   The Exception thrown when there is a full boot failure.
     */
    public function __construct(KernelInterface $kernel, \Exception $exception = null)
    {
        $this->kernel = $kernel;
        $this->exception = $exception;
    }

    /**
     * Gets the Kernel instance that triggered this event.
     *
     * @return KernelInterface
     *   The Kernel instance that triggered this event.
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Gets the exception instance when there is a full boot failure.
     *
     * @return \Exception
     *   The Exception instance or null if no failure occurred.
     */
    public function getException()
    {
        return $this->exception;
    }
}
