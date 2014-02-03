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
     * We should be able to create a new Insulin Kernel.
     */
    public function testConstructor()
    {
        $debug = true;

        $kernel = new Kernel($debug);
        $this->assertEquals($debug, $kernel->isDebug());
        $this->assertFalse($kernel->isBooted());
        $this->assertLessThanOrEqual(microtime(true), $kernel->getStartTime());
        $this->assertNull($kernel->getContainer());
    }

    /**
     * If we are cloning a new Kernel, confirm that is being reset.
     */
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
     * When creating a new kernel we should always have a root dir for Insulin.
     */
    public function testGetRootDir()
    {
        $kernel = new Kernel();
        $this->assertNotEmpty($kernel->getRootDir());
    }

    /**
     * New Kernel by default should return `UTF-8` charset.
     */
    public function testGetCharset()
    {
        $kernel = new Kernel();
        $this->assertSame('UTF-8', $kernel->getCharset());
    }

    /**
     * When booting the Kernel twice, it should only boot once.
     *
     * @group performance
     */
    public function testPerformanceBoot()
    {
        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('getBootstrapLevels', 'bootTo')
        );
        $kernel->expects($this->once())->method('getBootstrapLevels')->will(
            $this->returnValue(array(1))
        );

        /* @var $kernel \Insulin\Console\KernelInterface */
        $kernel->boot();
        $kernel->boot();
    }

    /**
     * Confirm that boot fails when not reaching first level.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Unit test for boot failure
     */
    public function testBootFailure()
    {
        $kernel = $this->getMockBuilder('Insulin\Console\Kernel')
            ->setMethods(array('getBootstrapLevels', 'bootTo'))
            ->setConstructorArgs(array(true))
            ->getMock();

        $kernel->expects($this->once())->method('getBootstrapLevels')->will(
            $this->returnValue(array(1))
        );
        $kernel->expects($this->once())->method('bootTo')->with(1)->will(
            $this->throwException(new \Exception('Unit test for boot failure'))
        );

        $kernel->boot();
    }

    /**
     * Confirm that we can shutdown the Kernel after a valid boot.
     */
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

    /**
     * Confirm that we can't shutdown the Kernel if there is no valid boot.
     */
    public function testShutdownsWhenNotBooted()
    {
        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('isBooted')
        );
        $kernel->expects($this->once())->method('isBooted')->will(
            $this->returnValue(false)
        );

        /* @var $kernel \Insulin\Console\Kernel */
        $kernel->shutdown();
    }

    /**
     * Confirm that we can get the booted level if kernel is booted.
     */
    public function testGetBootedLevel()
    {
        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('getBootstrapLevels', 'bootTo')
        );

        $kernel->expects($this->once())->method('getBootstrapLevels')->will(
            $this->returnValue(array(1, 2))
        );

        $kernel->boot();
        $this->assertEquals(2, $kernel->getBootedLevel());

    }

    /**
     * Confirm that the comments are stripped from PHP files correctly.
     */
    public function testStripComments()
    {
        if (!function_exists('token_get_all')) {
            $this->markTestSkipped('The function token_get_all() is not available.');

            return;
        }
        $source = <<<'EOF'
<?php

$string = 'string should not be   modified';


$heredoc = <<<HD


Heredoc should not be   modified


HD;

$nowdoc = <<<'ND'


Nowdoc should not be   modified


ND;

/**
 * some class comments to strip
 */
class TestClass
{
    /**
     * some method comments to strip
     */
    public function doStuff()
    {
        // inline comment
    }
}
EOF;
        $expected = <<<'EOF'
<?php
$string = 'string should not be   modified';
$heredoc =
<<<HD


Heredoc should not be   modified


HD;
$nowdoc =
<<<'ND'


Nowdoc should not be   modified


ND;
class TestClass
{
    public function doStuff()
    {
            }
}
EOF;

        $output = Kernel::stripComments($source);

        // Heredocs are preserved, making the output mixing unix and windows line
        // endings, switching to "\n" everywhere on windows to avoid failure.
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $expected = str_replace("\r\n", "\n", $expected);
            $output = str_replace("\r\n", "\n", $output);
        }

        $this->assertEquals($expected, $output);
    }

}
