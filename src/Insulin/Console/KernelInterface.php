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
     * Only bootstrap Insulin, without any SugarCRM specific code.
     *
     * Any code that operates on the Insulin installation, and not specifically
     * any SugarCRM directory, should bootstrap to this phase.
     */
    const BOOT_INSULIN = 1;

    /**
     * Set up and test for a valid SugarCRM root, either through the -r/--root options,
     * or evaluated based on the current working directory.
     *
     * Any code that interacts with an entire SugarCRM installation, and not a specific
     * site on the SugarCRM installation should use this bootstrap phase.
     */
    const BOOT_SUGAR_ROOT = 2;

    /**
     * Load the settings from the SugarCRM sites directory.
     *
     * This phase is commonly used for code that interacts with the SugarCRM install API,
     * as both install.php and update.php start at this phase.
     */
    const BOOT_SUGAR_CONFIGURATION = 3;

    /**
     * Connect to the SugarCRM database using the database credentials loaded
     * during the previous bootstrap phase.
     *
     * Any code that needs to interact with the SugarCRM database API needs to
     * be bootstrapped to at least this phase.
     */
    const BOOT_SUGAR_DATABASE = 4;

    /**
     * Fully initialize SugarCRM.
     *
     * Any code that interacts with the general SugarCRM API should be
     * bootstrapped to this phase.
     */
    const BOOT_SUGAR_FULL = 5;

    /**
     * Log in to the initialized SugarCRM site.
     *
     * This is the default bootstrap phase all commands will try to reach,
     * unless otherwise specified.
     *
     * This bootstrap phase is used after the site has been
     * fully bootstrapped.
     *
     * This phase will log you in to the SugarCRM site with the username
     * or user ID specified by the --user/ -u option.
     *
     * Use this bootstrap phase for your command if you need to have access
     * to information for a specific user, such as listing nodes that might
     * be different based on who is logged in.
     */
    const BOOT_SUGAR_LOGIN = 6;

    /**
     * Boots the current kernel.
     *
     * @api
     */
    public function boot();

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
     * @return Boolean
     *   TRUE if debug mode is enabled, FALSE otherwise.
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
     * @return integer
     *   The request start timestamp.
     *
     * @api
     */
    public function getStartTime();

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
     * Gets the log directory.
     *
     * @return string
     *   The log directory.
     *
     * @api
     */
    public function getLogDir();

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
