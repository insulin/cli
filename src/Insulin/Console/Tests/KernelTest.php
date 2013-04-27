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
        } else {
            mkdir(self::$sugarRoot);
        }

        foreach (self::$sugarFiles as $file) {
            if ('/' === substr($file, -1) && !is_dir($file)) {
                mkdir($file);
            } else {
                touch($file);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (array_reverse(self::$sugarFiles) as $file) {
            if ('/' === substr($file, -1)) {
                @rmdir($file);
            } else {
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

    public function testGetRootDir()
    {
        $kernel = new Kernel();
        $this->assertNotEmpty($kernel->getRootDir());
    }

    public function testGetCharset()
    {
        $kernel = new Kernel();
        $this->assertSame('UTF-8', $kernel->getCharset());
    }

    public function testPerformanceBoot()
    {
        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('getBootstrapLevels')
        );
        $kernel->expects($this->once())->method('getBootstrapLevels')->will(
            $this->returnValue(array())
        );

        // run once
        $kernel->boot();
        // run twice (should be booted already)
        $kernel->boot();
    }

    public function testBootInsulinLevel()
    {
        $debug = true;

        $kernel = new Kernel($debug);
        $bootLevel = $kernel->boot();

        $this->assertSame(Kernel::BOOT_INSULIN, $bootLevel);
        $this->assertTrue($kernel->isBooted());
    }

    public function testBootSugarRootLevel()
    {
        $kernel = $this->getMock(
            '\Insulin\Console\Kernel',
            array('getSugarRoot')
        );
        $kernel->expects($this->once())->method('getSugarRoot')->will(
            $this->returnValue(self::$sugarRoot)
        );

        $bootLevel = $kernel->boot();

        $this->assertSame(Kernel::BOOT_SUGAR_ROOT, $bootLevel);
        $this->assertTrue($kernel->isBooted());
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
            array(sys_get_temp_dir(), false),
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
            array(sys_get_temp_dir() . '/unexistent_path', false, 'InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider providerSetSugarRoot
     */
    public function testSetSugarRoot($path, $expectedResult, $expectedException = null)
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $kernel = new Kernel();
        $kernel->setSugarRoot($path);
        $this->assertEquals($expectedResult, $kernel->getSugarRoot());
    }

    public function providerSetSugarRoot()
    {
        return array(
            array(null, null, 'RuntimeException'),
            array('', null, 'RuntimeException'),
            array(sys_get_temp_dir(), null, 'RuntimeException'),
            array(sys_get_temp_dir() . '/insulin2_sugar', sys_get_temp_dir() . '/insulin2_sugar'),
            array(sys_get_temp_dir() . '/insulin2_sugar/include/MVC', null, 'RuntimeException'),
        );
    }

    public function testShutdownsWhenBooted()
    {
        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('isBooted')
        );
        $kernel->expects($this->once())->method('isBooted')->will(
            $this->returnValue(true)
        );

        $kernel->shutdown();
        $this->assertNull($kernel->getContainer());
    }

    /**
     * @dataProvider providerBootTo
     */
    public function testBootTo($level, $expectedResult, $expectedException = null)
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('getSugarRoot')
        );
        $kernel->expects($this->any())->method('getSugarRoot')->will(
            $this->returnValue(self::$sugarRoot)
        );

        $this->assertEquals($expectedResult, $kernel->bootTo($level));
    }

    public function providerBootTo()
    {
        return array(
            array(null, false, 'InvalidArgumentException'),
            array(-1, false, 'InvalidArgumentException'),
            array(Kernel::BOOT_INSULIN, true),
            array(Kernel::BOOT_SUGAR_ROOT, true),
        );
    }
}
