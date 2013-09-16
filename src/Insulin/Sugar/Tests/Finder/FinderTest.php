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

namespace Insulin\Sugar\Finder\Tests;

use Insulin\Sugar\Finder\Finder;

class FinderTest extends \PHPUnit_Framework_TestCase
{
    protected static $root;
    protected static $files;
    protected static $links;

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
        static::$links = array(
            static::$root . '/modules' => static::$root . '/sugar/modules',
            static::$root . '/sugar/custom' => static::$root . '/custom'
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

        foreach (static::$links as $target => $link) {
            symlink($target, $link);
        }

        file_put_contents(
            static::$root . '/sugar/sugar_version.php',
            '<?php
$sugar_version      = \'6.5.3\';
$sugar_db_version   = \'6.5.3\';
$sugar_flavor       = \'ENT\';
$sugar_build        = \'123\';
$sugar_timestamp    = \'2008-08-01 12:00am\';
'
        );
    }

    public static function tearDownAfterClass()
    {
        foreach (static::$links as $link) {
            @unlink($link);
        }

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
     * @dataProvider providerSetPath
     */
    public function testSetPath($path, $expectedPath, $lookup = true, $expectedException = null)
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $sugar = new Finder();
        $this->assertEquals($expectedPath, $sugar->setPath($path, $lookup)->getPath());
    }

    public function providerSetPath()
    {
        $dir = sys_get_temp_dir();

        return array(
            // lookup true
            array($dir . '/insulin/sugar', $dir . '/insulin/sugar'),
            array($dir . '/insulin/sugar/include', $dir . '/insulin/sugar'),
            array($dir . '/insulin/sugar/modules', $dir . '/insulin/sugar'),
            array($dir . '/insulin/custom', realpath($dir) . '/insulin/sugar'),
            array($dir . '/insulin/modules', null, true, '\Insulin\Sugar\Finder\Exception\RootNotFoundException'),
            array($dir . '/insulin', null, true, '\Insulin\Sugar\Finder\Exception\RootNotFoundException'),
            array(null, null, true, '\InvalidArgumentException'),
            // lookup false
            array($dir . '/insulin/sugar', $dir . '/insulin/sugar', false),
            array($dir . '/insulin/sugar/include', null, false, '\Insulin\Sugar\Finder\Exception\RootNotFoundException'),
            array(null, null, false, '\Insulin\Sugar\Finder\Exception\RootNotFoundException'),
        );
    }
}
