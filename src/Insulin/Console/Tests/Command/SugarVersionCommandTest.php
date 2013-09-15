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

use Insulin\Console\Application;
use Insulin\Console\Kernel;
use Insulin\Console\Command\SugarVersionCommand;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

class SugarVersionCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $insulin;

    protected function setUp()
    {
        $kernel = $this->getKernel(Kernel::BOOT_SUGAR_ROOT);

        $this->insulin = new Application($kernel);
        $this->insulin->setAutoExit(false);
    }

    public function testHelp()
    {
        $insulinTester = new ApplicationTester($this->insulin);
        $insulinTester->run(
            array('command' => 'help', 'command_name' => 'sugar:version')
        );
        $this->assertRegExp(
            '/The sugar:version command shows the current SugarCRM flavor, version and build number./',
            $insulinTester->getDisplay()
        );
    }

    public function testExecution()
    {
        $this->insulin->add(new SugarVersionCommand());
        $command = $this->insulin->find('sugar:version');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp(
            '/SugarCRM ENT 6.4.3 build 123/',
            $commandTester->getDisplay()
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testExecutionRequirements()
    {
        $kernel = $this->getKernel(Kernel::BOOT_INSULIN);
        $insulin = new Application($kernel);
        $insulin->setAutoExit(false);
        $insulin->add(new SugarVersionCommand());
        $command = $insulin->find('sugar:version');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));
    }

    /**
     * Gets a mock kernel to test the Insulin Application.
     *
     * @param $level
     *   The level of the boot reached.
     *
     * @return Kernel
     */
    private function getKernel($level)
    {
        $sugar = $this->getMock(
            'Insulin\Sugar\Sugar',
            array('getInfo')
        );
        $map = array(
            array('flavor', false, 'ENT'),
            array('version', false, '6.4.3'),
            array('build', false, '123'),
        );
        $sugar->expects($this->any())->method('getInfo')->will(
            $this->returnValueMap($map)
        );

        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('getBootedLevel', 'getRootDir', 'get')
        );
        $kernel->expects($this->any())->method('getBootedLevel')->will(
            $this->returnValue($level)
        );
        $kernel->expects($this->any())->method('getRootDir')->will(
            $this->returnValue(dirname(dirname(__DIR__)))
        );
        $kernel->expects($this->any())->method('get')->with('sugar')->will(
            $this->returnValue($sugar)
        );

        return $kernel;
    }
}
