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

use Doctrine\DBAL\DriverManager;

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
     *
     * Defines `sugarEntry` constant in order to be able to include main code,
     * plus adds this SugarCRM instance root directory to include path.
     */
    public function bootRoot()
    {
        defined('sugarEntry') || define('sugarEntry', true);

        chdir($this->getPath());
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

        $configFile = 'config.php';
        if (!is_file($configFile)) {
            throw new \RuntimeException('Cannot boot configuration: config.php not found.');
        }

        global $sugar_config;
        include $configFile;

        $override = 'config_override.php';
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

        if (empty($config['dbconfig'])) {
            throw new \RuntimeException(
                'Unable to boot database, undefined configuration data.'
            );
        }

        // FIXME: need to translate sugar db types to doctrine db types
        // we're currently only supporting mysql
        if ($config['dbconfig']['db_type'] === 'mysql') {
            $config['dbconfig']['db_type'] = 'pdo_mysql';
        }

        $params = array(
            'dbname' => $config['dbconfig']['db_name'],
            'driver' => $config['dbconfig']['db_type'],
            'host' => $config['dbconfig']['db_host_name'],
            'password' => $config['dbconfig']['db_password'],
            'port' => $config['dbconfig']['db_port'],
            'user' => $config['dbconfig']['db_user_name'],
        );

        $wrapper = $this->getDatabaseWrapper();
        if (!empty($wrapper)) {
            $params['wrapperClass'] = $wrapper;
        }

        $connection = DriverManager::getConnection($params);
        $connection->connect();
        $connection->close();

        return $connection;
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

        require_once 'include/entryPoint.php';
        require_once 'include/MVC/SugarApplication.php';
    }

    /**
     * {@inheritdoc}
     */
    public function localLogin($username = '')
    {
        $manager = $this->getBean('Users');

        if (empty($username)) {
            $user = $manager->getSystemUser();

        } else {
            $user = $manager->retrieve_by_string_fields(
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
            sprintf("Cannot login as '%s', user not found.", $username)
        );
    }

    /**
     * Retrieves database wrapper.
     *
     * @return \Doctrine\DBAL\Connection|null
     *   Wrapper class or `null` if none.
     */
    protected function getDatabaseWrapper()
    {
        return null;
    }

    /**
     * Retrieves bean based on supplied module name.
     *
     * @param string $module
     *   Module name.
     *
     * @return \SugarBean
     *   Bean instance.
     *
     * @throws \RuntimeException
     *   If no matching bean for supplied module name.
     */
    protected function getBean($module)
    {
        $bean = \BeanFactory::getBean($module);

        if (empty($bean)) {
            throw new \RuntimeException(
                sprintf("Unable to retrieve bean for '%s' module.", $module)
            );
        }

        return $bean;
    }
}
