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

abstract class SugarTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Version in format X.Y.Z (e.g.: 7.0.0).
     *
     * Set this one for the versions that need to be tested when extending your
     * Test.
     *
     * @var string $version
     */
    protected static $version;

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
     * @dataProvider providerGetInfo
     */
    public function testGetInfo($property, $expectedValue, $expectedException = null)
    {
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

        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $manager = new Manager();

        /* @var $sugar \Insulin\Sugar\SugarInterface */
        $sugar = $manager->get(static::$root . '/sugar');

        $this->assertInstanceOf('\Insulin\Sugar\Sugar', $sugar);
        $this->assertEquals($expectedValue, $sugar->getInfo($property));
    }

    public function providerGetInfo()
    {
        return array(
            array('flavor', 'ENT'),
            array('version', '6.5.3'),
            array('build', '123'),
            array('unknownProperty', null, 'InvalidArgumentException'),
        );
    }

    public function testGetInfoUnsupportedProperty()
    {
        $this->setExpectedException(
            '\Insulin\Sugar\Exception\RuntimeException',
            sprintf(
                "Unsupported property '%s' in current SugarCRM instance '%s'.",
                'flavor',
                static::$root . '/sugar'
            )
        );

        file_put_contents(
            self::$root . '/sugar/sugar_version.php',
            '<?php
$sugar_version      = \'6.5.3\';
'
        );

        $manager = new Manager();
        $sugar = $manager->get(static::$root . '/sugar');

        /* @var $sugar Finder */
        $sugar->getInfo('flavor', true);
    }
}
