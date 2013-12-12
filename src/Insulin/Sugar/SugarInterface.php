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

namespace Insulin\Sugar;

/**
 * Interface for Sugar API being used by Insulin.
 *
 * This will allow the Sugar API and Insulin to work independently and support
 * several versions as long as these methods are available on their
 * implementations.
 * Later we might release this interface and their implementations to be used
 * by other project's that might want to integrate with SugarCRM using PHP.
 */
interface SugarInterface
{
    /**
     * Creates a new Sugar instance proxy to wrap all Sugar element elegantly.
     *
     * @param string $path
     *   The real path to this Sugar instance.
     *
     * @api
     */
    public function __construct($path);

    /**
     * Retrieves current SugarCRM instance root directory.
     *
     * @return string
     *   Path to the current SugarCRM instance root directory.
     *
     * @api
     */
    public function getPath();

    /**
     * Gets SugarCRM full version information.
     *
     * @param string $property
     *   (optional) The property to retrieve can be 'build', 'flavor' or
     *   'version', defaults to the latter.
     * @param bool $refresh
     *   (optional) `true` if we want to re-read the file, defaults to `false`.
     *
     * @return mixed
     *   SugarCRM version value according to the supplied property.
     *
     * @throws \InvalidArgumentException
     *   If unknown property supplied.
     * @throws Exception\RuntimeException
     *   If the supplied property isn't found on this SugarCRM instance.
     *
     * @api
     */
    public function getInfo();

    /**
     * Boots this Sugar instance root folder.
     */
    public function bootRoot();

    /**
     * Boots configuration files.
     *
     * @param bool $refresh
     *   (optional) `true` if we want to re-read the configuration files,
     *   defaults to `false`.
     *
     * @return array
     *   An array of configuration values.
     *
     * @throws \RuntimeException
     *   If config file isn't found.
     */
    public function bootConfig();

    /**
     * Boots database.
     *
     * @return PDO
     *   Database handler.
     *
     * @throws \RuntimeException
     *   If database config is invalid or an unsupported driver is supplied.
     */
    public function bootDatabase();

    /**
     * Boots application.
     *
     * @api
     */
    public function bootApplication();

    /**
     * Performs a local login based on supplied username.
     *
     * @param string $username
     *   (optional) Username against which the login is performed, if none
     *   supplied system user is used instead.
     *
     * @return User
     *   Logged in user.
     *
     * @throws \RuntimeException
     *   If login fails.
     */
    public function localLogin();
}
