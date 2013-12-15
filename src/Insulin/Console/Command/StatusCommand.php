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

namespace Insulin\Console\Command;

use Insulin\Sugar\SugarInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command provides information about the current status of Insulin and
 * SugarCRM version on existing or provided path.
 *
 * The information provided depends on the maximum boot level reached.
 *
 * @fixme Insulin commands need to extend from InsulinCommand so it requires an Insulin Application
 *
 * @api
 */
class StatusCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('status')
            ->setAliases(array('st'))
            ->setDefinition(array(
                new InputOption('show-passwords', null, InputOption::VALUE_NONE, 'Show database password.'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'To output status in other formats.', 'table'),
            ))
            ->setDescription('Provides a birds-eye view of the current SugarCRM installation, if any.')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command shows the current SugarCRM status:

  <info>%command.full_name%</info>

You can also output the status in other formats by using the <comment>--format</comment> option:

  <info>%command.full_name% --format=json</info>

Output formats available: <comment>table</comment>, <comment>json</comment>.
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * Uses table helper for output.
     * @see http://symfony.com/doc/master/components/console/helpers/tablehelper.html
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data =
            $this->getInsulinInfo() +
            $this->getSugarBasicInfo() +
            $this->getSugarConfigurationInfo() +
            $this->getSugarMoreInfo();

        if (!$input->hasOption('show-passwords')) {
            unset($data['Database password']);
            unset($data['License download key']);
        }

        switch ($input->getOption('format')) {
            case 'table':

                /* @var $table TableHelper */
                $table = $this->getApplication()->getHelperSet()->get('table');
                $table->setLayout(TableHelper::LAYOUT_COMPACT);

                // prepare data for TableHelper
                // TODO move this to a class so we can reuse this type of output (e.g: for vardefs)
                foreach ($data as $key => $value) {

                    // do some fixes based on keys
                    if ($key === 'Database') {
                        $value = $value ? 'Connected' : 'Not connected';
                    } elseif ($key === 'Developer mode') {
                        $value = $value ? 'Active' : 'Inactive';
                    } elseif (is_bool($value)) {
                        $value = 'True';
                    }

                    $table->addRow(array($key, $value));
                }

                $table->render($output);

                break;
            case 'json':
                $output->writeln(json_encode($data));
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported format "%s".', $input->getOption('format')));
        }
    }

    protected function getInsulinInfo()
    {
        /* @var $kernel \Insulin\Console\KernelInterface */
        $kernel = $this->getApplication()->getKernel();

        if ($kernel->getBootedLevel() < $kernel::BOOT_INSULIN) {
            return array();
        }

        return array(
            // not the same but probably we shouldn't give too much support for 5.3 now that 5.5 is out
            'PHP executable' => defined('PHP_BINARY') ? PHP_BINARY : PHP_BINDIR,
            'PHP configuration' => php_ini_loaded_file(),
            'PHP OS' => PHP_OS,
            'Insulin version' => $kernel->getVersion(),
        );

    }

    protected function getSugarBasicInfo()
    {
        /* @var $kernel \Insulin\Console\KernelInterface */
        $kernel = $this->getApplication()->getKernel();

        if ($kernel->getBootedLevel() < $kernel::BOOT_SUGAR_ROOT) {
            return array();
        }

        /* @var $sugar SugarInterface */
        $sugar = $kernel->get('sugar');

        $flavor = $sugar->getInfo('flavor');
        $version = $sugar->getInfo('version');
        $build = $sugar->getInfo('build');

        return array(
            'SugarCRM version' => sprintf('%s %s build %s', $flavor, $version, $build),
            'SugarCRM root' => $sugar->getPath(),
        );
    }

    protected function getSugarConfigurationInfo()
    {
        /* @var $kernel \Insulin\Console\KernelInterface */
        $kernel = $this->getApplication()->getKernel();

        if ($kernel->getBootedLevel() < $kernel::BOOT_SUGAR_CONFIGURATION) {
            return array();
        }

        /* @var $sugar SugarInterface */
        $sugar = $kernel->get('sugar');

        // FIXME we need a getConfig as API from SugarInterface
        $config = $sugar->bootConfig();

        $dbType = $config['dbconfig']['db_type'];
        $dbHostname = $config['dbconfig']['db_host_name'];
        $dbPort = $config['dbconfig']['db_port'];
        $dbUsername = $config['dbconfig']['db_user_name'];
        $dbPassword = $config['dbconfig']['db_password'];
        $dbName = $config['dbconfig']['db_name'];

        $data = array(
            'Database driver' => $dbType,
            'Database hostname' => $dbHostname,
            'Database port' => $dbPort,
            'Database username' => $dbUsername,
            'Database password' => $dbPassword,
            'Database name' => $dbName,
        );

        $isConnected = ($kernel->getBootedLevel() >= $kernel::BOOT_SUGAR_DATABASE);
        $data += array(
            'Database' => $isConnected,
        );

        $data += array(
            'Cache directory path' => $config['cache_dir'],
            'Site URI' => $config['site_url'],
            'Upload directory path' => $config['upload_dir'],
            'Developer mode' => !empty($config['developerMode']),
            'Logger level' => $config['logger']['level'],
            // FIXME support SugarCRM logger file with dates
            // see sugarcrm/include/SugarLogger/SugarLogger.php on _doInitialization
            'Logger file' => $config['logger']['file']['name'] . $config['logger']['file']['ext'],
        );

        if (!empty($config['full_text_engine'])) {
            // TODO this is weird but I'm pretty sure that Sugar only supports 1 FTS at a time
            // but configuration gets this as an array?
            $ftsEngine = reset($config['full_text_engine']);
            $data += array(
                'Full text engine' => key($config['full_text_engine']),
                'Full text engine host' => $ftsEngine['host'],
                'Full text engine port' => $ftsEngine['port'],
            );
        }

        return $data;
    }

    protected function getSugarMoreInfo()
    {
        /* @var $kernel \Insulin\Console\KernelInterface */
        $kernel = $this->getApplication()->getKernel();

        if ($kernel->getBootedLevel() < $kernel::BOOT_SUGAR_FULL) {
            return array();
        }

        // TODO find a way to abstract this information into the SugarProxy
        // yes this returns an Administration class instance :(
        $admin = \Administration::getSettings('info');

        return array(
            'Database expected version' => $admin->settings['info_sugar_version'],
            // TODO check how this comes on CE edition and if we need to check for flavor first
            'License number of users' => $admin->settings['license_users'],
            'License number of offline clients' => $admin->settings['license_num_lic_oc'],
            'License expire date' => $admin->settings['license_expire_date'],
            'License download key' => $admin->settings['license_key'],
        );
    }
}
