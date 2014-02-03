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

use Insulin\Sugar\Finder\Finder;

/**
 * Factory to get a Sugar instance based on a given version and path.
 *
 * This class will select the correct abstract version of your SugarCRM
 * instance.
 */
class Manager
{
    /**
     * Search for SugarCRM instance root directory inside supplied path.
     *
     * @param string $path
     *   Path to a SugarCRM instance.
     *
     * @return SugarInterface
     *
     * @api
     */
    public function find($path)
    {
        $finder = new Finder();
        $finder->setPath($path, true);
        $rootPath = $finder->getPath();

        return $this->getProxy($this->getVersion($rootPath), $rootPath);
    }

    /**
     * Gets a proxy to interact with the Sugar instance living on the given
     * path.
     *
     * @param string $path
     *   Path to a SugarCRM instance.
     *
     * @return SugarInterface
     *   Returns the Sugar proxy for the Sugar instance on that path.
     *
     * @throws Exception\RootNotFoundException
     *   If invalid path is given or no Sugar instance exists on that path.
     *
     * @api
     */
    public function get($path)
    {
        if (!$this->isRoot($path)) {
            throw new Exception\RootNotFoundException(
                sprintf(
                    "SugarCRM instance root directory not found in path '%s'.",
                    $path
                )
            );
        }
        return $this->getProxy($this->getVersion($path), $path);
    }

    /**
     * Returns a Sugar proxy based on the given version.
     *
     * @param string $version
     *   The version of the Sugar instance to best match the proxy object.
     * @param string $path
     *   The path to the Sugar instance.
     * @return SugarInterface
     *   The best proxy found that matches your sugar version.
     *
     * @throws Exception\RuntimeException when no supported version was found.
     */
    protected function getProxy($version, $path)
    {
        $ver = explode('.', $version);
        $ns = 'Insulin\\Sugar\\Versions';
        while ($ver) {
            try {
                $r = new \ReflectionClass($ns . '\\' . 'Sugar' . implode('', $ver));
                if ($r->isSubclassOf('Insulin\\Sugar\\Sugar') && !$r->isAbstract()) {
                    return $r->newInstance($path);
                }
            } catch (\ReflectionException $e) {
            }

            array_pop($ver);
        }

        throw new Exception\RuntimeException(
            sprintf("Unsupported version: '%s'.", $version)
        );
    }

    /**
     * Checks if supplied path contains a valid SugarCRM instance root
     * directory.
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
     * Gets the Sugar version that exists on the supplied path.
     *
     * This method does not check for possible errors because its for internal
     * use only. All other methods check if everything is correct before
     * getting here, and if we return an empty version, other methods are
     * covering that case.
     *
     * @param string $path
     *   Path to a SugarCRM instance root.
     *
     * @return string
     *   The version got on this Sugar path.
     */
    private function getVersion($path)
    {
        // due to Sugar code
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }

        include $path . '/sugar_version.php';
        $version = $sugar_version;
        // and now we have a version to proceed

        return $version;
    }
}
