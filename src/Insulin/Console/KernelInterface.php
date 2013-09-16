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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Kernel is the heart of the Insulin system.
 *
 * It manages an SugarCRM integration.
 *
 * @api
 */
interface KernelInterface extends \Serializable
{

    /**
     * Initializes Insulin, no Sugar specific code is available so far.
     *
     * Any code that operates on Insulin and has no dependencies on Sugar
     * directory/installation should bootstrap to this level.
     */
    const BOOT_INSULIN = 1;

    /**
     * Sets up Sugar root based on the `-p/--path` options, or falls back to the
     * current working directory if none supplied.
     *
     * Any code that interacts with the Sugar directory, should bootstrap to
     * this level.
     */
    const BOOT_SUGAR_ROOT = 2;

    /**
     * Loads Sugar settings from config files.
     */
    const BOOT_SUGAR_CONFIGURATION = 3;

    /**
     * Connects to Sugar database using the credentials loaded in the previous
     * level.
     *
     * Any code that needs to interact with the Sugar database, should
     * bootstrap to this level.
     */
    const BOOT_SUGAR_DATABASE = 4;

    /**
     * This is the first level where Sugar specific code is made available,
     * which makes this level commonly used by commands that need to interact
     * with Sugar settings, database and code.
     */
    const BOOT_SUGAR_FULL = 5;

    /**
     * Logs in to the Sugar instance with a user supplied by the `-u/--user`
     * options, or falls back to the system user.
     *
     * This level is reached after Sugar has been fully bootstrapped, it's also
     * the level where all commands will try to reach by default, unless another
     * level is specified.
     *
     * Any command that needs access to specific user data should bootstrap to
     * this level.
     */
    const BOOT_SUGAR_LOGIN = 6;

    /**
     * Initializes the current kernel.
     *
     * @api
     */
    public function initialize();

    /**
     * Boots the current kernel.
     *
     * @api
     */
    public function boot();

    /**
     * Returns the maximum level reached after booting with success, false
     * otherwise.
     *
     * All levels available are defined as BOOT_* constants on KernelInterface.
     *
     * @return bool|int
     *   The current boot level of the kernel.
     *
     * @api
     */
    public function getBootedLevel();

    /**
     * Shutdowns the kernel.
     *
     * This method is mainly useful when doing functional testing.
     *
     * @api
     */
    public function shutdown();

    /**
     * Gets the name of the kernel.
     *
     * @return string
     *   The kernel name.
     *
     * @api
     */
    public function getName();

    /**
     * Gets the version of the kernel.
     *
     * @return string
     *   The kernel version.
     *
     * @api
     */
    public function getVersion();

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool
     *   True if debug mode is enabled, false otherwise.
     *
     * @api
     */
    public function isDebug();

    /**
     * Gets the application root dir.
     *
     * @return string
     *   The application root dir
     *
     * @api
     */
    public function getRootDir();

    /**
     * Gets the current container.
     *
     * @return ContainerInterface
     *   A ContainerInterface instance.
     *
     * @api
     */
    public function getContainer();

    /**
     * Gets the request start time (not available if debug is disabled).
     *
     * @return int
     *   The request start timestamp.
     *
     * @api
     */
    public function getStartTime();

    /**
     * Returns Insulin home directory for storage of cache and other related
     * data.
     *
     * @return string
     *   The Insulin home directory.
     *
     * @api
     */
    public function getHomeDir();

    /**
     * Gets the cache directory.
     *
     * @return string
     *   The cache directory.
     *
     * @api
     */
    public function getCacheDir();

    /**
     * Gets the charset of the application.
     *
     * @return string
     *   The charset.
     *
     * @api
     */
    public function getCharset();

    /**
     * Gets the path for the current SugarCRM instance.
     *
     * @return string
     *   Path to a SugarCRM instance root directory.
     *
     * @api
     */
    public function getSugarPath();

    /**
     * Sets the path for the current SugarCRM instance.
     *
     * @param string $path
     *   Path to a SugarCRM instance root directory.
     *
     * @return Kernel
     *   Kernel instance.
     *
     * @api
     */
    public function setSugarPath($path);

    /**
     * Gets a service by id.
     *
     * @param string $id
     *  The service id
     *
     * @return object
     *   The service
     */
    public function get($id);
}
