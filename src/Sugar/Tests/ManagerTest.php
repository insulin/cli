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

namespace Insulin\Sugar\Tests;

use Insulin\Sugar\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected static $root;
    protected static $files;

    public static function setupBeforeClass()
    {
        static::$root = sys_get_temp_dir() . '/insulin';
        static::$files = array(
            static::$root . '/modules/',
            static::$root . '/sugar/',
            static::$root . '/sugar/custom/',
            static::$root . '/sugar/include/',
            static::$root . '/sugar/include/entryPoint.php',
            static::$root . '/sugar/include/MVC/',
            static::$root . '/sugar/include/MVC/SugarApplication.php',
            static::$root . '/sugar/config.php',
            static::$root . '/sugar/sugar_version.php',
        );

        if (is_dir(static::$root)) {
            static::tearDownAfterClass();
        } else {
            mkdir(static::$root);
        }

        foreach (static::$files as $file) {
            if ('/' === substr($file, -1) && !is_dir($file)) {
                mkdir($file);
            } else {
                touch($file);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (array_reverse(static::$files) as $file) {
            if ('/' === substr($file, -1)) {
                @rmdir($file);
            } else {
                @unlink($file);
            }
        }

        @rmdir(static::$root);
    }

    /**
     * Tests availability of Sugar proxies supported on Insulin.
     *
     * @param string $version
     *   The version to test.
     * @param bool $isSupported
     *   True if this version should be supported, false otherwise.
     *
     * @dataProvider supportedVersions
     */
    public function testGet($version, $isSupported)
    {
        file_put_contents(
            static::$root . '/sugar/sugar_version.php',
            '<?php
$sugar_version      = \'' . $version . '\';
$sugar_db_version   = \'' . $version . '\';
$sugar_flavor       = \'ENT\';
$sugar_build        = \'123\';
$sugar_timestamp    = \'2008-08-01 12:00am\';
'
        );

        if (!$isSupported) {
            $this->setExpectedException(
                '\Insulin\Sugar\Exception\RuntimeException',
                sprintf("Unsupported version: '%s'.", $version)
            );
        }

        $manager = new Manager();

        $sugar = $manager->get(static::$root . '/sugar');
        $this->assertInstanceOf('Insulin\Sugar\SugarInterface', $sugar);
    }

    public function supportedVersions()
    {
        return array(
            array('7.0.0', true),
            array('6.5.0', true),
            array('6.4.0', false),
            array('6.0.0', false),
        );
    }

    /**
     * @expectedException \Insulin\Sugar\Exception\RootNotFoundException
     */
    public function testGetRootNotFound()
    {
        $manager = new Manager();
        $manager->get(static::$root . '/sugar/include');
    }

    public function testFind()
    {
        file_put_contents(
            static::$root . '/sugar/sugar_version.php',
            '<?php
$sugar_version      = \'7.0.0\';
$sugar_db_version   = \'\';
$sugar_flavor       = \'ENT\';
$sugar_build        = \'123\';
$sugar_timestamp    = \'2013-08-08 12:00am\';
'
        );

        $manager = new Manager();
        $sugar = $manager->find(static::$root . '/sugar/include');
        $this->assertInstanceOf('Insulin\Sugar\SugarInterface', $sugar);
    }
}
