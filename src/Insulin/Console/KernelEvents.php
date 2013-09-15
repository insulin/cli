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

final class KernelEvents
{
    /**
     * The `kernel.boot_success` event is triggered when full boot ends
     * successfully.
     *
     * The event listener receives an Insulin\Console\KernelBootEvent instance.
     *
     * @var string
     */
    const BOOT_SUCCESS = 'kernel.boot_success';

    /**
     * The `kernel.boot_failure` event is triggered when full boot fails.
     *
     * The event listener receives an Insulin\Console\KernelBootEvent instance.
     *
     * @var string
     */
    const BOOT_FAILURE = 'kernel.boot_failure';
}
