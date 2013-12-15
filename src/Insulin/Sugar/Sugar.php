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

namespace Insulin\Sugar;

/**
 * Abstraction to handle a SugarCRM instance.
 *
 * This class abstracts the way how one can interact with a SugarCRM instance
 * regardless of it's version. Use classes provided on the `Versions/` folder
 * to provide special cases functionality.
 *
 * @see SugarManager::get()
 */
abstract class Sugar implements SugarInterface
{
    protected $path;

    /**
     * {@inheritdoc}
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo($property = 'version', $refresh = false)
    {
        static $info = array(
            'flavor' => false,
            'version' => false,
            'build' => false,
        );

        if (!isset($info[$property])) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Unknown SugarCRM property '%s'.",
                    $property
                )
            );
        }

        if (false === $info[$property] || $refresh) {
            include $this->getPath() . '/sugar_version.php';
            foreach ($info as $k => &$value) {
                $field = 'sugar_' . $k;
                $value = isset($$field) ? $$field : false;
            }
        }

        if (false === $info[$property]) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Unsupported property '%s' in current SugarCRM instance '%s'.",
                    $property,
                    $this->getPath()
                )
            );
        }

        return $info[$property];
    }

    /**
     * {@inheritdoc}
     */
    public function bootRoot()
    {
        defined('sugarEntry') || define('sugarEntry', true);
    }

    /**
     * {@inheritdoc}
     */
    public function bootConfig($refresh = false)
    {
        static $config = null;

        if ($config !== null && !$refresh) {
            return $config;
        }

        $configFile = $this->getPath() . '/config.php';
        if (!is_file($configFile)) {
            throw new \RuntimeException('Cannot boot configuration: config.php not found.');
        }

        global $sugar_config;
        include $configFile;

        $override = $this->getPath() . '/config_override.php';
        if (is_file($override)) {
            include $override;
        }

        $config = $sugar_config;

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function bootDatabase()
    {
        $config = $this->bootConfig();

        if (!isset($config['dbconfig']) || empty($config['dbconfig'])) {
            throw new \RuntimeException(
                'Unable to connect to database, undefined configuration data.'
            );
        }

        $type = $config['dbconfig']['db_type'];
        if (!in_array($type, \PDO::getAvailableDrivers())) {
            throw new \RuntimeException(
                sprintf(
                    "Unable to connect to database, unsupported driver '%s'.",
                    $type
                )
            );
        }

        $hostname = $config['dbconfig']['db_host_name'];
        $port = $config['dbconfig']['db_port'];
        $username = $config['dbconfig']['db_user_name'];
        $password = $config['dbconfig']['db_password'];
        $database = $config['dbconfig']['db_name'];

        $dbh = new \PDO(
            sprintf(
                '%s:host=%s;port=%s;dbname=%s',
                $type,
                $hostname,
                $port,
                $database
            ),
            $username,
            $password
        );

        return $dbh;
    }

    /**
     * {@inheritdoc}
     */
    public function bootApplication()
    {
        global $sugar_flavor;
        global $sugar_version;
        // global $sugar_build;
        global $sugar_config;
        global $locale;
        global $db;
        global $beanList;
        global $beanFiles;
        // global $moduleList;
        // global $modInvisList;
        // global $adminOnlyList;
        // global $modules_exempt_from_availability_check;

        chdir($this->getPath());

        require_once 'include/entryPoint.php';
        require_once 'include/MVC/SugarApplication.php';
    }

    /**
     * {@inheritdoc}
     */
    public function localLogin($username = '')
    {
        $factory = \BeanFactory::getBean('Users');

        if (empty($username)) {
            $user = $factory->getSystemUser();

        } else {
            $user = $factory->retrieve_by_string_fields(
                array('user_name' => $username)
            );
        }

        if (!empty($user)) {
            $GLOBALS['current_user'] = $user;

            return $user;
        }

        if (empty($username)) {
            throw new \RuntimeException(
                "Cannot login as administrator. Please check that you have at "
                . "least one administrator created on your instance."
            );
        }

        throw new \RuntimeException(
            sprintf(
                "Cannot login as '%s', user not found.",
                $username
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemSettings($category, $reload = false)
    {
        $admin = \BeanFactory::getBean('Administration');
        $admin->retrieveSettings($category, $reload);
        return $admin->settings;
    }
}
