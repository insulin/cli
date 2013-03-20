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
    /**
     * @var string
     */
    protected static $sugarRoot;

    /**
     * @var Application
     */
    protected $insulin;

    public static function setupBeforeClass()
    {
        self::$sugarRoot = sys_get_temp_dir() . '/insulin2_sugar';

        if (is_dir(self::$sugarRoot)) {
            self::tearDownAfterClass();

        } else {
            mkdir(self::$sugarRoot);
        }

        file_put_contents(
            self::$sugarRoot . '/sugar_version.php',
            '<?php
$sugar_version      = \'6.4.3\';
$sugar_db_version   = \'6.4.3\';
$sugar_flavor       = \'ENT\';
$sugar_build        = \'123\';
$sugar_timestamp    = \'2008-08-01 12:00am\';
'
        );
    }

    public static function tearDownAfterClass()
    {
        @unlink(self::$sugarRoot . '/sugar_version.php');
    }

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
     * Gets a mock kernel to test the Insulin Application.
     *
     * @param $level
     *   The level of the boot reached.
     *
     * @return Kernel
     */
    private function getKernel($level)
    {
        $kernel = $this->getMock(
            'Insulin\Console\Kernel',
            array('boot', 'getRootDir', 'getSugarRoot')
        );
        $kernel->expects($this->any())->method('boot')->will(
            $this->returnValue($level)
        );
        $kernel->expects($this->any())->method('getRootDir')->will(
            $this->returnValue(dirname(dirname(__DIR__)))
        );
        $kernel->expects($this->any())->method('getSugarRoot')->will(
            $this->returnValue(self::$sugarRoot)
        );

        return $kernel;
    }
}
