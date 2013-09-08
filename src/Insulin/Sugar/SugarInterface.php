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
     * Retrieves current SugarCRM instance root directory.
     *
     * @return string
     *   Path to the current SugarCRM instance root directory.
     *
     * @api
     */
    public function getPath();

    /**
     * Sets the path for the current SugarCRM instance.
     *
     * @param string $path
     *   Path to a SugarCRM instance.
     * @param bool $lookup
     *   (optional) True forces lookup for a SugarCRM instance root directory on supplied
     *   path, defaults to false.
     *
     * @return Sugar
     *   Current Sugar instance.
     *
     * @throws \InvalidArgumentException
     *   If supplied path is invalid.
     * @throws \Insulin\Sugar\Exception\RootNotFoundException
     *   If supplied path does not contain a valid SugarCRM instance root
     *   directory.
     *
     * @api
     */
    public function setPath($path, $lookup);

    /**
     * Lookup SugarCRM instance root directory inside supplied path.
     *
     * @param string $path
     *   Lookup path to a SugarCRM instance.
     *
     * @return string
     *   Returns SugarCRM instance root.
     *
     * @throws \InvalidArgumentException
     *   If supplied path is invalid.
     * @throws \Insulin\Sugar\Exception\RootNotFoundException
     *   If supplied path does not contain a valid SugarCRM instance root
     *   directory.
     *
     * @api
     */
    public function lookupPath($path);
}
