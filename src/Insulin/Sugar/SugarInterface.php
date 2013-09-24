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
     * Initializes a Sugar Instance to be ready for invokes.
     *
     * @api
     */
    public function init();

    /**
     * Retrieves current SugarCRM instance root directory.
     *
     * @return string
     *   Path to the current SugarCRM instance root directory.
     *
     * @api
     */
    public function getPath();
}
