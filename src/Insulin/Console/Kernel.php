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

namespace Insulin\Console;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The Kernel is the heart of the Insulin system.
 *
 * It manages an SugarCRM integration.
 *
 * @api
 */
class Kernel extends ContainerAware implements KernelInterface
{
    protected $rootDir;
    protected $debug;
    /**
     * True if kernel is initialized and ready to boot, false otherwise.
     *
     * @var bool
     */
    protected $initialized;
    protected $booted;
    protected $bootLevel;
    protected $startTime;
    protected $classes;
    protected $sugarPath;

    const NAME = 'Insulin';
    const VERSION = '2.0';
    const MAJOR_VERSION = '2';
    const MINOR_VERSION = '0';
    const RELEASE_VERSION = '0';
    const EXTRA_VERSION = '';

    /**
     * Constructor.
     *
     * @param Boolean $debug
     *   Whether to enable debugging or not.
     *
     * @api
     */
    public function __construct($debug = false)
    {
        $this->debug = (Boolean) $debug;
        $this->booted = false;
        $this->classes = array();

        if ($this->debug) {
            $this->startTime = microtime(true);
        }

        $this->init();
    }

    public function init()
    {
        if ($this->debug) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        } else {
            ini_set('display_errors', 0);
        }
    }

    public function __clone()
    {
        if ($this->debug) {
            $this->startTime = microtime(true);
        }

        $this->booted = false;
        $this->container = null;
    }

    /**
     * Boots the current kernel.
     *
     * @api
     */
    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->initializeContainer();
        $this->initialized = true;
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if (true === $this->booted) {
            return $this->bootLevel;
        }

        $this->initialize();

        $bootLevel = 0;
        try {
            $levels = $this->getBootstrapLevels();
            foreach ($levels as $level) {
                $this->bootTo($level);
                $bootLevel = $level;
            }

        } catch (\Exception $e) {

            $this->container->get('dispatcher')->dispatch(
                KernelEvents::BOOT_FAILURE,
                new KernelBootEvent($level, $e->getMessage())
            );
        }

        $this->booted = $bootLevel > 0;
        $this->bootLevel = $bootLevel;

        $this->container->get('dispatcher')->dispatch(
            KernelEvents::BOOT_SUCCESS,
            new KernelBootEvent($this->bootLevel)
        );

        return $this->bootLevel;
    }

    public function getBootstrapLevels()
    {
        static $functions = array(
            self::BOOT_INSULIN,
            self::BOOT_SUGAR_ROOT,
            self::BOOT_SUGAR_CONFIGURATION,
            self::BOOT_SUGAR_DATABASE,
            self::BOOT_SUGAR_FULL,
            self::BOOT_SUGAR_LOGIN,
        );

        $result = $functions;

        return $result;
    }

    public function bootTo($level)
    {
        switch ($level) {
            case self::BOOT_INSULIN:
                return true;
                break;
            case self::BOOT_SUGAR_ROOT:
                $path = $this->sugarPath;
                if (!empty($path)) {
                    $this->get('sugar')->setPath($path);
                } else {
                    $this->get('sugar')->setPath($this->getCwd(), true);
                }

                if (!defined('sugarEntry')) {
                    define('sugarEntry', true);
                }
                return true;
                break;
            // TODO give support to other run levels
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unknown "%s" phase given.',
                        $level
                    )
                );
                break;
        }
    }

    /**
     * Shutdowns the kernel.
     *
     * This method is mainly useful when doing functional testing.
     *
     * @api
     */
    public function shutdown()
    {
        if (false === $this->isBooted()) {
            return;
        }

        $this->booted = false;
        $this->container = null;
    }

    /**
     * Gets the name of the kernel.
     *
     * @return string
     *   The kernel name.
     *
     * @api
     */
    public function getName()
    {
        return $this::NAME;
    }

    /**
     * Gets the version of the kernel.
     *
     * @return string
     *   The kernel version.
     *
     * @api
     */
    public function getVersion()
    {
        return $this::VERSION;
    }

    /**
     * Checks if is booted.
     *
     * @return Boolean
     *   TRUE if is booted, FALSE otherwise.
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return Boolean
     *   TRUE if debug mode is enabled, FALSE otherwise.
     *
     * @api
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Gets the insulin root dir.
     *
     * @return string
     *   The insulin root dir.
     *
     * @api
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = str_replace('\\', '/', dirname($r->getFileName()));
        }

        return $this->rootDir;
    }

    /**
     * Gets the current container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     *   A ContainerInterface instance.
     *
     * @api
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Used internally.
     */
    public function setClassCache(array $classes)
    {
        file_put_contents(
            $this->getCacheDir() . '/classes.map',
            sprintf('<?php return %s;', var_export($classes, true))
        );
    }

    /**
     * Gets the request start time (not available if debug is disabled).
     *
     * @return integer
     *   The request start timestamp.
     *
     * @api
     */
    public function getStartTime()
    {
        return $this->debug ? $this->startTime : -INF;
    }

    /**
     * Gets the cache directory.
     *
     * @return string
     *   The cache directory.
     *
     * @api
     */
    public function getCacheDir()
    {
        return $this->getRootDir() . '/cache/insulin';
    }

    /**
     * Gets the log directory.
     *
     * @return string
     *   The log directory.
     *
     * @api
     */
    public function getLogDir()
    {
        return $this->getRootDir() . '/logs';
    }

    /**
     * Gets the charset of the insulin.
     *
     * @return string
     *   The charset
     *
     * @api
     */
    public function getCharset()
    {
        return 'UTF-8';
    }

    /**
     * Gets the container class.
     *
     * @return string
     *   The container class
     */
    protected function getContainerClass()
    {
        return $this->getName() . ($this->debug ? 'Debug' : '') . 'ProjectContainer';
    }

    /**
     * Gets the container's base class.
     *
     * All names except Container must be fully qualified.
     *
     * @return string
     */
    protected function getContainerBaseClass()
    {
        return 'Container';
    }

    /**
     * Initializes the service container.
     *
     * The cached version of the service container is used when fresh, otherwise
     * the container is built.
     */
    protected function initializeContainer()
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir() . '/' . $class . '.php', $this->debug);
        $fresh = true;
        if (!$cache->isFresh()) {
            $container = $this->buildContainer();
            $this->dumpContainer(
                $cache,
                $container,
                $class,
                $this->getContainerBaseClass()
            );

            $fresh = false;
        }

        require_once $cache;

        $this->container = new $class();
        $this->container->set('kernel', $this);

        $this->container->set('dispatcher', new EventDispatcher());
        $this->container->set('sugar', new \Insulin\Sugar\Sugar());

        if (!$fresh && $this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp(
                $this->container->getParameter('kernel.cache_dir')
            );
        }
    }

    /**
     * Returns the kernel parameters.
     *
     * @return array
     *   An array of kernel parameters
     */
    protected function getKernelParameters()
    {
        return array_merge(
            array(
                'kernel.root_dir' => $this->rootDir,
                'kernel.debug' => $this->debug,
                'kernel.name' => self::NAME,
                'kernel.cache_dir' => $this->getCacheDir(),
                'kernel.logs_dir' => $this->getLogDir(),
                'kernel.charset' => $this->getCharset(),
                'kernel.container_class' => $this->getContainerClass(),
            ),
            $this->getEnvParameters()
        );
    }

    /**
     * Gets the environment parameters.
     *
     * Only the parameters starting with "INSULIN__" are considered.
     *
     * @return array
     *   An array of parameters
     */
    protected function getEnvParameters()
    {
        $parameters = array();
        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'INSULIN__')) {
                $parameters[strtolower(
                    str_replace('__', '.', substr($key, 9))
                )] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Builds the service container.
     *
     * @return ContainerBuilder
     *   The compiled service container
     *
     * @throws \RuntimeException if unable to create a directory for container.
     */
    protected function buildContainer()
    {
        $dirs = array(
            'cache' => $this->getCacheDir(),
            'logs' => $this->getLogDir()
        );
        foreach ($dirs as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true)) {
                    throw new \RuntimeException(
                        sprintf(
                            "Unable to create the %s directory (%s)\n",
                            $name,
                            $dir
                        )
                    );
                }
            } elseif (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        "Unable to write in the %s directory (%s)\n",
                        $name,
                        $dir
                    )
                );
            }
        }

        $container = $this->getContainerBuilder();

        $container->addObjectResource($this);
        $container->compile();

        return $container;
    }

    /**
     * Gets a new ContainerBuilder instance used to build the service container.
     *
     * @return ContainerBuilder
     */
    protected function getContainerBuilder()
    {
        return new ContainerBuilder(new ParameterBag($this->getKernelParameters()));
    }

    /**
     * Dumps the service container to PHP code in the cache.
     *
     * @param ConfigCache $cache
     *   The config cache
     * @param ContainerBuilder $container
     *   The service container
     * @param string $class
     *   The name of the class to generate
     * @param string $baseClass
     *   The name of the container's base class
     */
    protected function dumpContainer(
        ConfigCache $cache,
        ContainerBuilder $container,
        $class,
        $baseClass
    ) {
        // cache the container
        $dumper = new PhpDumper($container);
        $content = $dumper->dump(
            array('class' => $class, 'base_class' => $baseClass)
        );
        if (!$this->debug) {
            $content = self::stripComments($content);
        }

        $cache->write($content, $container->getResources());
    }

    /**
     * Removes comments from a PHP source string.
     *
     * We don't use the PHP php_strip_whitespace() function
     * as we want the content to be readable and well-formatted.
     *
     * @param string $source
     *   A PHP string.
     *
     * @return string
     *   The PHP string with the comments removed.
     */
    protected static function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (!in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= $token[1];
            }
        }

        // replace multiple new lines with a single newline
        $output = preg_replace(array('/\s+$/Sm', '/\n+/S'), "\n", $output);

        return $output;
    }

    public function serialize()
    {
        return serialize(array($this->debug));
    }

    public function unserialize($data)
    {
        list($debug) = unserialize($data);

        $this->__construct($debug);
    }

    /**
     * Returns the current working directory.
     *
     * This is the directory as it was when insulin was started, not the
     * directory we are currently in. For that, use getcwd() directly.
     */
    public function getCwd()
    {
        // FIXME some cache...
        //if ($path = insulin_get_context('INSULIN_OLDCWD')) {
        //    return $path;
        //}
        // We use PWD if available because getcwd() resolves symlinks, which
        // could take us outside of the SugarCRM root, making it impossible to
        // find.$_SERVER['PWD'] isn't set on windows and generates a Notice.
        $path = isset($_SERVER['PWD']) ? $_SERVER['PWD'] : '';
        if (empty($path)) {
            $path = getcwd();
        }

        // Convert windows paths.
        $path = $this->convertPath($path);

        // Save original working dir case some command wants it.
        //insulin_set_context('INSULIN_OLDCWD', $path);

        return $path;
    }

    /**
     * Converts a Windows path (dir1\dir2\dir3) into a Unix path (dir1/dir2/dir3).
     * Also converts a cygwin "drive emulation" path (/cygdrive/c/dir1) into a
     * proper drive path, still with Unix slashes (c:/dir1).
     */
    protected function convertPath($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/^\/cygdrive\/([A-Za-z])(.*)$/', '\1:\2', $path);

        return $path;
    }

    /**
     * Sets the path for the current SugarCRM instance.
     *
     * @param string $path
     *   Path to a SugarCRM instance root directory.
     *
     * @throws \InvalidArgumentException if invalid $prop given.
     * @throws \RuntimeException if unsupported property requested for current SugarCRM instance.
     *
     * @return Kernel
     *   Kernel instance.
     *
     * @api
     */
    public function setSugarPath($path)
    {
        $this->sugarPath = $path;
        return $this;
    }

    /**
     * Gets a service by id.
     *
     * @param string $id
     *  Service id.
     *
     * @return object
     *   Service instance.
     *
     * @api
     */
    public function get($id)
    {
        return $this->container->get($id);
    }
}
