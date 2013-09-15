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

use Insulin\Sugar\Exception\RootNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * Abstraction to handle a SugarCRM instance.
 *
 * This class abstracts the way how one can interact with a SugarCRM instance
 * regardless of it's version.
 */
class Sugar implements SugarInterface
{
    /**
     * The path for the SugarCRM instance.
     *
     * @var string
     *
     * @see Sugar::setPath() where this path is set.
     */
    protected $path;

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function setPath($path, $lookup = false)
    {
        if (!$lookup && !$this->isRoot($path)) {
            throw new RootNotFoundException(
                sprintf(
                    "SugarCRM instance root directory not found in path '%s'.",
                    $path
                )
            );
        }

        if ($lookup) {
            $path = $this->lookupPath($path);
        }

        $this->path = $path;
        return $this;
    }

    /**
     * Checks if supplied path matches a valid SugarCRM instance root directory.
     *
     * @param string $path
     *   Path to a SugarCRM instance root.
     *
     * @return bool
     *   True if supplied path is a valid SugarCRM instance root directory,
     *   false otherwise.
     *
     * @throws \InvalidArgumentException
     *   If supplied path does not match a valid SugarCRM instance root
     *   directory.
     */
    protected function isRoot($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid path given: '%s'.",
                    $path
                )
            );
        }

        static $candidates = array(
            'include/entryPoint.php',
            'include/MVC/SugarApplication.php',
            'sugar_version.php'
        );

        foreach ($candidates as $candidate) {
            if (!is_file($path . '/' . $candidate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Search for SugarCRM instance root directory inside supplied path.
     *
     * @param string $path
     *   Search for a SugarCRM instance root inside supplied path.
     * @param bool $followLinks
     *   (optional) True forces symlinks to be followed, defaults to false.
     *
     * @return string
     *   Path to the SugarCRM instance root directory.
     *
     * @throws \InvalidArgumentException
     *   If supplied path is invalid.
     * @throws \Insulin\Sugar\Exception\RootNotFoundException
     *   If supplied path does not contain a valid SugarCRM instance root
     *   directory.
     */
    protected function find($path, $followLinks = false)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid path given: "%s".',
                    $path
                )
            );
        }

        $tmpPath = $path;

        while ($tmpPath) {
            $finder = new Finder();

            if ($followLinks && is_link($tmpPath)) {
                $tmpPath = realpath($tmpPath);
            }

            $finder->files()->name('sugar_version.php')->depth(0)->in($tmpPath);

            if (1 === $finder->count() && $this->isRoot($tmpPath)) {
                return $tmpPath;
            }

            $tmpPath = $this->shiftPathUp($tmpPath);
        }

        throw new RootNotFoundException(
            sprintf(
                "SugarCRM instance root directory not found in path '%s'.",
                $path
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function lookupPath($path)
    {
        try {
            return $this->find($path);

        } catch (RootNotFoundException $e) {
            return $this->find($path, true);
        }
    }

    /**
     * Retrieves supplied path upper directory.
     *
     * @param string $path
     *   Path to directory.
     *
     * @return string
     *   Returns the upper directory path based on supplied path, or an empty
     *   string if none is found.
     */
    private function shiftPathUp($path)
    {
        $path = explode('/', $path);
        array_pop($path);
        return implode('/', $path);
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
     * @throws \RuntimeException
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
            throw new \RuntimeException(
                sprintf(
                    "Unsupported property '%s' in current SugarCRM instance '%s'.",
                    $property,
                    $this->getPath()
                )
            );
        }

        return $info[$property];
    }

    /**
     * FIXME this will need to be moved to a SugarWrapper that isn't PSR-2 valid
     */
    public function init()
    {
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }
    }
}
