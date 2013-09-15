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

    /**
     * The `kernel.boot_level` event is triggered when booting to a certain
     * level.
     *
     * This can be used to track the current level being booted as well to
     * extend the functionality of a certain boot level.
     *
     * The event listener receives an Insulin\Console\KernelBootLevelEvent
     * instance.
     */
    const BOOT_LEVEL = 'kernel.boot_level';

    /**
     * The `kernel.boot_level_before` event is triggered before a boot to a
     * certain level is triggered.
     *
     * The event listener receives an Insulin\Console\KernelBootLevelEvent
     * instance.
     */
    const BOOT_LEVEL_BEFORE = 'kernel.boot_level_before';

    /**
     * The `kernel.boot_level_success` event is triggered each time a boot to a
     * certain level succeeds.
     *
     * The event listener receives an Insulin\Console\KernelBootLevelEvent
     * instance.
     */
    const BOOT_LEVEL_SUCCESS = 'kernel.boot_level_success';

    /**
     * The `kernel.boot_level_failure` event is triggered each time a boot to a
     * certain level fails.
     *
     * The event listener receives an Insulin\Console\KernelBootLevelEvent
     * instance.
     */
    const BOOT_LEVEL_FAILURE = 'kernel.boot_level_failure';
}
