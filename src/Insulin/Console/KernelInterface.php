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
use Symfony\Component\Config\Loader\LoaderInterface;

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
     * Loads the container configuration
     *
     * @param LoaderInterface $loader
     *   A LoaderInterface instance.
     *
     * @api
     */
    public function registerContainerConfiguration(LoaderInterface $loader);

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
}
