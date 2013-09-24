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
 * Abstraction to handle a SugarCRM instance.
 *
 * This class abstracts the way how one can interact with a SugarCRM instance
 * regardless of it's version. Use classes provided on the `Versions/` folder
 * to provide special cases functionality.
 *
 * @see SugarManager::get()
 */
abstract class Sugar implements SugarInterface
{
    protected $path;

    /**
     * {@inheritdoc}
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Initializes this Sugar instance to be ready to call Sugar code.
     */
    public function init()
    {
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets SugarCRM full version information.
     *
     * @param string $property
     *   The property to retrieve, can be 'version', 'build' or 'flavor'.
     * @param bool $refresh
     *   True if we want to re-read the file.
     *
     * @return mixed
     *   SugarCRM version value according to the supplied property.
     *
     * @throws \InvalidArgumentException
     *   If unknown property supplied.
     * @throws Exception\RuntimeException
     *   If the supplied property isn't found on this SugarCRM instance.
     */
    public function getInfo($property = 'version', $refresh = false)
    {
        static $info = array(
            'flavor' => false,
            'version' => false,
            'build' => false,
        );

        if (!isset($info[$property])) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Unknown SugarCRM property '%s'.",
                    $property
                )
            );
        }

        if (false === $info[$property] || $refresh) {
            include $this->getPath() . '/sugar_version.php';
            foreach ($info as $k => &$value) {
                $field = 'sugar_' . $k;
                $value = isset($$field) ? $$field : false;
            }
        }

        if (false === $info[$property]) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Unsupported property '%s' in current SugarCRM instance '%s'.",
                    $property,
                    $this->getPath()
                )
            );
        }

        return $info[$property];
    }
}
