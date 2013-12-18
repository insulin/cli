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

    protected $sugar;

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

    protected function setUp()
    {
        file_put_contents(
            self::$root . '/sugar/sugar_version.php',
            '<?php
$sugar_version = \'' . static::$version . '\';
'
        );

        $manager = new Manager();

        /* @var $sugar \Insulin\Sugar\SugarInterface */
        $this->sugar = $manager->get(static::$root . '/sugar');
    }

    protected function tearDown()
    {
        file_put_contents(self::$root . '/sugar/sugar_version.php', '');

        $this->sugar = null;
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

        $this->assertInstanceOf('\Insulin\Sugar\Sugar', $this->sugar);
        $this->assertEquals($expectedValue, $this->sugar->getInfo($property));
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
$sugar_version = \'6.5.3\';
'
        );

        /* @var $sugar Finder */
        $this->sugar->getInfo('flavor', true);
    }

    public function testBootRoot()
    {
        $this->sugar->bootRoot();

        $this->assertTrue(defined('sugarEntry'));
        $this->assertEquals(realpath($this->sugar->getPath()), getcwd());
    }

    public function testBootConfig()
    {
        file_put_contents(
            static::$root . '/sugar/config.php',
            '<?php $sugar_config = array(\'default_module\' => \'Home\');'
        );

        $this->sugar->bootRoot();
        $this->sugar->bootConfig(true);

        file_put_contents(
            static::$root . '/sugar/config.php',
            ''
        );

        $expectedValue = array('default_module' => 'Home');

        $this->assertEquals($expectedValue, $this->sugar->bootConfig());
    }

    public function testBootConfigWithOverride()
    {
        file_put_contents(
            static::$root . '/sugar/config.php',
            '<?php $sugar_config = array(\'default_module\' => \'Home\');'
        );

        file_put_contents(
            static::$root . '/sugar/config_override.php',
            '<?php $sugar_config[\'default_module\'] = \'Accounts\';'
        );

        $this->sugar->bootRoot();

        $expectedValue = array('default_module' => 'Accounts');

        $this->assertEquals($expectedValue, $this->sugar->bootConfig(true));

        file_put_contents(static::$root . '/sugar/config.php', '');

        unlink(static::$root . '/sugar/config_override.php');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testBootConfigFailure()
    {
        @unlink(static::$root . '/sugar/config.php');

        $this->sugar->bootRoot();
        $this->sugar->bootConfig(true);

        touch(static::$root . '/sugar/config.php');
    }

    public function testBootDatabase()
    {
        $config = array(
            'dbconfig' => array(
                'db_name' => 'sugardb',
                'db_type' => 'mysql',
                'db_host_name' => 'localhost',
                'db_password' => 'sugaronsteroids',
                'db_port' => '',
                'db_user_name' => 'insulin',
            ),
        );

        $wrapper = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->setMethods(array('connect', 'close'))
            ->disableOriginalConstructor()
            ->getMock();

        $sugar = $this->getMockBuilder('\Insulin\Sugar\Sugar')
            ->setMethods(array('bootConfig', 'getDatabaseWrapper'))
            ->disableOriginalConstructor()
            ->getMock();

        $sugar->expects($this->once())->method('bootConfig')->will(
            $this->returnValue($config)
        );

        $sugar->expects($this->once())->method('getDatabaseWrapper')->will(
            $this->returnValue($wrapper)
        );

        $connection = $sugar->bootDatabase();

        $this->assertEquals($config['dbconfig']['db_name'], $connection->getDatabase());
        $this->assertEquals('pdo_mysql', $connection->getDriver()->getName());
        $this->assertEquals($config['dbconfig']['db_host_name'], $connection->getHost());
        $this->assertEquals($config['dbconfig']['db_password'], $connection->getPassword());
        $this->assertEquals($config['dbconfig']['db_user_name'], $connection->getUsername());
        $this->assertEquals($config['dbconfig']['db_port'], $connection->getPort());
    }

    /**
     * @dataProvider providerBootDatabaseFailure
     */
    public function testBootDatabaseFailure($config, $expectedException)
    {
        $sugar = $this->getMockBuilder('\Insulin\Sugar\Sugar')
            ->setMethods(array('bootConfig'))
            ->disableOriginalConstructor()
            ->getMock();

        $sugar->expects($this->any())->method('bootConfig')->will(
            $this->returnValue($config)
        );

        $this->setExpectedException($expectedException);

        $sugar->bootDatabase();
    }

    public function providerBootDatabaseFailure()
    {
        return array(
            array(
                array(),
                'RuntimeException',
            ),
            array(
                array(
                    'dbconfig' => array(
                        'db_type' => '',
                        'db_host_name' => '',
                        'db_port' => '',
                        'db_user_name' => '',
                        'db_password' => '',
                        'db_name' => '',
                    ),
                ),
                'Doctrine\DBAL\DBALException',
            ),
        );
    }

    public function testBootApplication()
    {
        $this->sugar->bootRoot();
        $this->sugar->bootApplication();

        $files = get_included_files();

        $this->assertContains(
            realpath(static::$root . '/sugar/include/entryPoint.php'),
            $files
        );
        $this->assertContains(
            realpath(static::$root . '/sugar/include/MVC/SugarApplication.php'),
            $files
        );
    }

    public function testLocalLoginWithoutUsernameReturnsSystemUser()
    {
        $user = new \stdClass;

        $bean = $this->getMockBuilder('\stdClass')
            ->setMethods(array('getSystemUser'))
            ->getMock();

        $bean->expects($this->once())->method('getSystemUser')->will(
            $this->returnValue($user)
        );

        $sugar = $this->getMockBuilder('\Insulin\Sugar\Sugar')
            ->setMethods(array('getBean'))
            ->disableOriginalConstructor()
            ->getMock();

        $sugar->expects($this->once())->method('getBean')->will(
            $this->returnValue($bean)
        );

        $this->assertEquals($user, $sugar->localLogin());
        $this->assertEquals($user, $GLOBALS['current_user']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot login as administrator. Please check
     *   that you have at least one administrator created on your instance.
     */
    public function testLocalLoginWithoutUsernameFailure()
    {
        $bean = $this->getMockBuilder('\stdClass')
            ->setMethods(array('getSystemUser'))
            ->getMock();

        $sugar = $this->getMockBuilder('\Insulin\Sugar\Sugar')
            ->setMethods(array('getBean'))
            ->disableOriginalConstructor()
            ->getMock();

        $sugar->expects($this->once())->method('getBean')->will(
            $this->returnValue($bean)
        );

        $sugar->localLogin();
    }

    public function testLocalLoginWithUsername()
    {
        $user = new \stdClass;
        $user->user_name = 'yoda';

        $bean = $this->getMockBuilder('\stdClass')
            ->setMethods(array('retrieve_by_string_fields'))
            ->getMock();

        $bean->expects($this->once())->method('retrieve_by_string_fields')->will(
            $this->returnValue($user)
        );

        $sugar = $this->getMockBuilder('\Insulin\Sugar\Sugar')
            ->setMethods(array('getBean'))
            ->disableOriginalConstructor()
            ->getMock();

        $sugar->expects($this->once())->method('getBean')->will(
            $this->returnValue($bean)
        );

        $this->assertEquals($user, $sugar->localLogin($user->user_name));
        $this->assertEquals($user, $GLOBALS['current_user']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot login as 'yoda', user not found.
     */
    public function testLocalLoginWithUsernameFailure()
    {
        $bean = $this->getMockBuilder('\stdClass')
            ->setMethods(array('retrieve_by_string_fields'))
            ->getMock();

        $sugar = $this->getMockBuilder('\Insulin\Sugar\Sugar')
            ->setMethods(array('getBean'))
            ->disableOriginalConstructor()
            ->getMock();

        $sugar->expects($this->once())->method('getBean')->will(
            $this->returnValue($bean)
        );

        $sugar->localLogin('yoda');
    }
}
