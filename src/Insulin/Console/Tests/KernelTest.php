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

        /* @var $kernel \Insulin\Console\KernelInterface */
        $kernel->boot();
        $kernel->boot();
    }

    /**
     * @dataProvider providerBootToFailure
     */
    public function testBootToFailure($level, $expectedResult, $expectedException = null)
    {
        if (!empty($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        $kernel = new Kernel();

        $this->assertEquals($expectedResult, $kernel->bootTo($level));
    }

    public function providerBootToFailure()
    {
        return array(
            array(null, false, 'InvalidArgumentException'),
            array(-1, false, 'InvalidArgumentException'),
        );
    }

    public function testBootInsulinLevel()
    {
        $debug = true;

        $kernel = new Kernel($debug);
        $bootLevel = $kernel->boot();

        $this->assertSame(Kernel::BOOT_INSULIN, $bootLevel);
        $this->assertTrue($kernel->isBooted());
    }

    /**
     * @dataProvider providerBootSugarRootLevel
     */
    public function testBootSugarRootLevel($withPath)
    {
        $sugar = $this->getMock(
            'Insulin\Sugar\Sugar',
            array('setPath')
        );
        $sugar->expects($this->once())->method('setPath')->will(
            $this->returnValue($sugar)
        );

        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('get')
        );
        $kernel->expects($this->once())->method('get')->with('sugar')->will(
            $this->returnValue($sugar)
        );

        /* @var $kernel \Insulin\Console\Kernel */
        if ($withPath) {
            $kernel->setSugarPath('/path/to/sugar');
        }

        $bootLevel = $kernel->boot();

        $this->assertSame(Kernel::BOOT_SUGAR_ROOT, $bootLevel);
        $this->assertTrue($kernel->isBooted());
    }

    public function providerBootSugarRootLevel()
    {
        return array(
            array(true),
            array(false),
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

        /* @var $kernel \Insulin\Console\Kernel */
        $kernel->shutdown();
        $this->assertNull($kernel->getContainer());
    }
}
