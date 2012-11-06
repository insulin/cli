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

use Insulin\Console\Application;
use Insulin\Console\Kernel;
use Symfony\Component\Console\Tester\ApplicationTester;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testCoreCommandsAvailableWithBootInsulin()
    {
        $kernel = $this->getKernel(Kernel::BOOT_INSULIN);

        $insulin = new Application($kernel);
        $insulin->setAutoExit(false);

        $insulinTester = new ApplicationTester($insulin);
        $insulinTester->run(array('command' => 'list'));

        // FIXME add core commands like download sugar ce?
    }

    public function testCoreCommandsAvailableWithBootSugarConfiguration()
    {
        $kernel = $this->getKernel(Kernel::BOOT_SUGAR_ROOT);

        $insulin = new Application($kernel);
        $insulin->setAutoExit(false);

        $insulinTester = new ApplicationTester($insulin);
        $insulinTester->run(array('command' => 'list'));

        $this->assertRegExp('/sugar:version/', $insulinTester->getDisplay());
    }

    /**
     * Gets a mock kernel to test the Insulin Application.
     *
     * @param $level
     *   The level of the boot reached.
     *
     * @return KernelInterface
     */
    private function getKernel($level)
    {
        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('boot', 'getRootDir')
        );
        $kernel->expects($this->any())->method('boot')->will(
            $this->returnValue($level)
        );
        $kernel->expects($this->any())->method('getRootDir')->will(
            $this->returnValue(dirname(__DIR__))
        );

        return $kernel;
    }
}
