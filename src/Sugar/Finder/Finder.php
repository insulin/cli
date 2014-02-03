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

namespace Insulin\Sugar\Finder;

use Insulin\Sugar\Manager;
use Insulin\Sugar\Sugar;
use Symfony\Component\Finder\Finder as FileFinder;

/**
 * Abstraction to handle SugarCRM instance discovery.
 *
 * This class abstracts the way how one can locate a SugarCRM instance in the
 * file system, based on a given path.
 */
class Finder implements FinderInterface
{
    /**
     * The path for the SugarCRM instance.
     *
     * @var string
     *
     * @see Finder::setPath() where this path is set.
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
        if (!$lookup && !$this->hasSugar($path)) {
            throw new Exception\RootNotFoundException(
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
     * Checks if supplied path contains a valid SugarCRM instance.
     *
     * @param string $path
     *   Path to a SugarCRM instance root.
     *
     * @return bool
     *   True if supplied path is a valid SugarCRM instance root directory,
     *   false otherwise.
     */
    protected function hasSugar($path)
    {
        try {
            $manager = new Manager();
            $sugar = $manager->get($path);

        } catch (\Exception $e) {
            return false;
        }

        return $sugar instanceof Sugar;
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
     * @throws Exception\RootNotFoundException
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
            $finder = new FileFinder();

            if ($followLinks && is_link($tmpPath)) {
                $tmpPath = realpath($tmpPath);
            }

            $finder->files()->name('sugar_version.php')->depth(0)->in($tmpPath);

            if ($finder->count() === 1 && $this->hasSugar($tmpPath)) {
                return $tmpPath;
            }

            $tmpPath = $this->shiftPathUp($tmpPath);
        }

        throw new Exception\RootNotFoundException(
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

        } catch (Exception\RootNotFoundException $e) {
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
}
