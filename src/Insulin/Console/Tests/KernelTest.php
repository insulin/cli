<?php

/*
 * This file is part of the Insulin CLI
 *
 * Copyright (c) 2008-2012 Filipe Guerra, João Morais
 * http://cli.sugarmeetsinsulin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Insulin\Console\Tests;

use Insulin\Console\Kernel;

class KernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected static $sugarRoot;

    /**
     * @var array
     */
    protected static $sugarFiles;

    public static function setupBeforeClass()
    {
        self::$sugarRoot = sys_get_temp_dir() . '/insulin2_sugar';
        self::$sugarFiles = array(
            self::$sugarRoot . '/include/',
            self::$sugarRoot . '/include/entryPoint.php',
            self::$sugarRoot . '/include/MVC/',
            self::$sugarRoot . '/include/MVC/SugarApplication.php',
            self::$sugarRoot . '/config.php',
            self::$sugarRoot . '/sugar_version.php',
        );

        if (is_dir(self::$sugarRoot)) {
            self::tearDownAfterClass();
        }
        else {
            mkdir(self::$sugarRoot);
        }

        foreach (self::$sugarFiles as $file) {
            if ('/' === substr($file, -1) && !is_dir($file)) {
                mkdir($file);
            }
            else {
                touch($file);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (array_reverse(self::$sugarFiles) as $file) {
            if ('/' === substr($file, -1)) {
                @rmdir($file);
            }
            else {
                @unlink($file);
            }
        }
    }

    public function testConstructor()
    {
        $debug = true;

        $kernel = new Kernel($debug);
        $this->assertEquals($debug, $kernel->isDebug());
        $this->assertFalse($kernel->isBooted());
        $this->assertLessThanOrEqual(microtime(true), $kernel->getStartTime());
        $this->assertNull($kernel->getContainer());
    }

    public function testClone()
    {
        $debug = true;
        $kernel = new Kernel($debug);

        $clone = clone $kernel;

        $this->assertEquals($debug, $clone->isDebug());
        $this->assertFalse($clone->isBooted());
        $this->assertLessThanOrEqual(microtime(true), $clone->getStartTime());
        $this->assertNull($clone->getContainer());
    }

    /**
     * @dataProvider providerIsSugarRoot
     */
    public function testIsSugarRoot($path, $expectedResult, $expectedException = null)
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $kernel = new Kernel();
        $this->assertEquals($kernel->isSugarRoot($path), $expectedResult);
    }

    public function providerIsSugarRoot()
    {
        return array(
            array(null, false, 'InvalidArgumentException'),
            array(sys_get_temp_dir() . '/insulin2_sugar', true),
            array(sys_get_temp_dir() . '/unexistent_path', false, 'InvalidArgumentException'),
            array(sys_get_temp_dir(), false)
        );
    }

    /**
     * @dataProvider providerLocateSugarRoot
     */
    public function testLocateSugarRoot($path, $expectedResult, $expectedException = null)
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $kernel = new Kernel();
        $this->assertEquals($expectedResult, $kernel->locateSugarRoot($path));
    }

    public function providerLocateSugarRoot()
    {
        return array(
            array(null, false, 'RuntimeException'),
            array(sys_get_temp_dir(), false, 'RuntimeException'),
            array(sys_get_temp_dir() . '/insulin2_sugar', sys_get_temp_dir() . '/insulin2_sugar'),
            array(sys_get_temp_dir() . '/insulin2_sugar/include/MVC', sys_get_temp_dir() . '/insulin2_sugar'),
            array(sys_get_temp_dir() . '/unexistent_path', false, 'InvalidArgumentException')
        );
    }
}
