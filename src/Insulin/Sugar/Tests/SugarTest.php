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

use Insulin\Sugar\Sugar;

class SugarTest extends \PHPUnit_Framework_TestCase
{
    protected static $root;
    protected static $files;
    protected static $links;

    public static function setupBeforeClass()
    {
        self::$root = sys_get_temp_dir() . '/insulin';
        self::$files = array(
            self::$root . '/modules/',
            self::$root . '/sugar/',
            self::$root . '/sugar/custom/',
            self::$root . '/sugar/include/',
            self::$root . '/sugar/include/entryPoint.php',
            self::$root . '/sugar/include/MVC/',
            self::$root . '/sugar/include/MVC/SugarApplication.php',
            self::$root . '/sugar/config.php',
            self::$root . '/sugar/sugar_version.php',
        );
        self::$links = array(
            self::$root . '/modules' => self::$root . '/sugar/modules',
            self::$root . '/sugar/custom' => self::$root . '/custom'
        );

        if (is_dir(self::$root)) {
            self::tearDownAfterClass();
        } else {
            mkdir(self::$root);
        }

        foreach (self::$files as $file) {
            if ('/' === substr($file, -1) && !is_dir($file)) {
                mkdir($file);
            } else {
                touch($file);
            }
        }

        foreach (self::$links as $target => $link) {
            symlink($target, $link);
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$links as $link) {
            @unlink($link);
        }

        foreach (array_reverse(self::$files) as $file) {
            if ('/' === substr($file, -1)) {
                @rmdir($file);
            } else {
                @unlink($file);
            }
        }

        @rmdir(self::$root);
    }

    /**
     * @dataProvider providerSetPath
     */
    public function testSetPath($path, $expectedPath, $lookup = true, $expectedException = null)
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $sugar = new Sugar;
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
            array($dir . '/insulin/modules', null, true, '\Insulin\Sugar\Exception\RootNotFoundException'),
            array($dir . '/insulin', null, true, '\Insulin\Sugar\Exception\RootNotFoundException'),
            array(null, null, true, '\InvalidArgumentException'),
            // lookup false
            array($dir . '/insulin/sugar', $dir . '/insulin/sugar', false),
            array($dir . '/insulin/sugar/include', null, false, '\Insulin\Sugar\Exception\RootNotFoundException'),
            array(null, null, false, '\InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider providerGetInfo
     */
    public function testGetInfo($property, $expectedValue, $expectedException = null)
    {
        file_put_contents(
            self::$root . '/sugar/sugar_version.php',
            '<?php
$sugar_version      = \'6.4.3\';
$sugar_db_version   = \'6.4.3\';
$sugar_flavor       = \'ENT\';
$sugar_build        = \'123\';
$sugar_timestamp    = \'2008-08-01 12:00am\';
'
        );

        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $sugar = $this->getMock(
            'Insulin\Sugar\Sugar',
            array('getPath')
        );
        $sugar->expects($this->any())->method('getPath')->will(
            $this->returnValue(self::$root . '/sugar')
        );

        /* @var $sugar \Insulin\Sugar\Sugar */
        $this->assertEquals($expectedValue, $sugar->getInfo($property));
    }

    public function providerGetInfo()
    {
        return array(
            array('flavor', 'ENT'),
            array('version', '6.4.3'),
            array('build', '123'),
            array('unknownProperty', null, 'InvalidArgumentException'),
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetInfoUnsupportedProperty()
    {
        file_put_contents(
            self::$root . '/sugar/sugar_version.php',
            '<?php
$sugar_version      = \'6.4.3\';
'
        );

        $sugar = $this->getMock(
            'Insulin\Sugar\Sugar',
            array('getPath')
        );
        $sugar->expects($this->any())->method('getPath')->will(
            $this->returnValue(self::$root . '/sugar')
        );

        /* @var $sugar Sugar */
        $sugar->getInfo('flavor', true);
    }
}
