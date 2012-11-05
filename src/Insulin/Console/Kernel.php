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

namespace Insulin\Console;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * The Kernel is the heart of the Insulin system.
 *
 * It manages an SugarCRM integration.
 *
 * @api
 */
class Kernel implements KernelInterface
{
    protected $rootDir;
    protected $debug;
    protected $booted;
    protected $bootLevel;
    protected $startTime;
    protected $classes;
    protected $sugarRoot;

    const NAME = 'Insulin';
    const VERSION = '2.0';
    const MAJOR_VERSION = '2';
    const MINOR_VERSION = '0';
    const RELEASE_VERSION = '0';
    const EXTRA_VERSION = '';

    /**
     * Only bootstrap Insulin, without any Sugar specific code.
     *
     * Any code that operates on the Insulin installation, and not specifically
     * any Sugar directory, should bootstrap to this phase.
     */
    const BOOT_INSULIN = 1;

    /**
     * Set up and test for a valid sugar root, either through the -r/--root options,
     * or evaluated based on the current working directory.
     *
     * Any code that interacts with an entire Sugar installation, and not a specific
     * site on the Sugar installation should use this bootstrap phase.
     */
    const BOOT_SUGAR_ROOT = 2;

    /**
     * Load the settings from the Sugar sites directory.
     *
     * This phase is commonly used for code that interacts with the Sugar install API,
     * as both install.php and update.php start at this phase.
     */
    const BOOT_SUGAR_CONFIGURATION = 3;

    /**
     * Connect to the Sugar database using the database credentials loaded
     * during the previous bootstrap phase.
     *
     * Any code that needs to interact with the Sugar database API needs to
     * be bootstrapped to at least this phase.
     */
    const BOOT_SUGAR_DATABASE = 4;

    /**
     * Fully initialize Sugar.
     *
     * Any code that interacts with the general Sugar API should be
     * bootstrapped to this phase.
     */
    const BOOT_SUGAR_FULL = 5;

    /**
     * Log in to the initialized Sugar site.
     *
     * This is the default bootstrap phase all commands will try to reach,
     * unless otherwise specified.
     *
     * This bootstrap phase is used after the site has been
     * fully bootstrapped.
     *
     * This phase will log you in to the sugar site with the username
     * or user ID specified by the --user/ -u option.
     *
     * Use this bootstrap phase for your command if you need to have access
     * to information for a specific user, such as listing nodes that might
     * be different based on who is logged in.
     */
    const BOOT_SUGAR_LOGIN = 6;

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
        }
        else {
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
    public function boot()
    {
        if (true === $this->booted) {
            return $this->bootLevel;
        }

        //$name = $this->getCommandName($input);
        // FIXME we don't need to bootstrap to last run level if we are already running a command
        /*
        if (!empty($name)) {
            // TODO search for command location
            // TODO $phase = check level of bootstrap required for this command
            // TODO $this->bootStrapToPhase($phase)
            // TODO $this->registerCommands($name)
        }
        */

        // try to bootstrap to max level and see what commands can we offer
        $bootLevel = 0;
        try {
            $levels = $this->getBootstrapLevels();
            foreach ($levels as $level) {
                if ($this->isDebug()) {
                    /*
                    $output->write(
                        sprintf("Attempting reach phase '%d'\t", $level)
                    );
                    */
                }
                $this->bootTo($level);
                if ($this->isDebug()) {
                    //$output->writeln(sprintf('<info>[done]</info>.'));
                }
                $bootLevel = $level;
            }
            // FIXME this should catch other type of exception...
        } catch (\Exception $e) {
            /*$output->writeln('<error>[error]</error>');
            $output->writeln(
                sprintf(
                    "Unable to reach '%d' phase due to:\n %s",
                    $maxPhase + 1,
                    $e->getMessage()
                )
            );
            */
        }
        if ($this->isDebug()) {
            /*
            $output->writeln(sprintf('Max phase reached "%d".', $maxPhase));
            */
        }

        // TODO init container
        //$this->initializeContainer();

        $this->booted = true;
        $this->bootLevel = $bootLevel;

        return $this->bootLevel;
    }

    public function getBootstrapLevels()
    {
        static $functions = array(
            self::BOOT_INSULIN,
            self::BOOT_SUGAR_ROOT,
            // TODO give support to other run levels
            /*
            self::BOOT_SUGAR_CONFIGURATION,
            self::BOOT_SUGAR_DATABASE,
            self::BOOT_SUGAR_FULL,
            self::BOOT_SUGAR_LOGIN,
            */
        );

        $result = $functions;

        return $result;
    }


    public function bootTo($level)
    {
        switch ($level) {
            case self::BOOT_INSULIN :
                return true;
                break;

            case self::BOOT_SUGAR_ROOT :
                $this->getSugarRoot();

                if (!defined('sugarEntry')) {
                    define('sugarEntry', true);
                }
                break;

            // TODO give support to other run levels

            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unknown "%s" phase given.',
                    $level
                ));
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
        if (false === $this->booted) {
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
    public function getName() {
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
    public function getVersion() {
        return $this::VERSION;
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return Boolean
     *   TRUE if debug mode is enabled, FALSE otherwise
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
     * @return ContainerInterface A ContainerInterface instance
     *
     * @api
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Loads the PHP class cache.
     *
     * @param string $name      The cache name prefix
     * @param string $extension File extension of the resulting file
     */
    public function loadClassCache($name = 'classes', $extension = '.php')
    {
        if (!$this->booted && is_file($this->getCacheDir() . '/classes.map')) {
            ClassCollectionLoader::load(
                include($this->getCacheDir() . '/classes.map'),
                $this->getCacheDir(),
                $name,
                $this->debug,
                false,
                $extension
            );
        }
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
        return $this->rootDir . '/cache/insulin';
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
        return $this->rootDir . '/logs';
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
        return $this->name . ucfirst(
            $this->environment
        ) . ($this->debug ? 'Debug' : '') . 'ProjectContainer';
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
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    protected function initializeContainer()
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir(
        ) . '/' . $class . '.php', $this->debug);
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
        $bundles = array();
        foreach ($this->bundles as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
        }

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
                    throw new \RuntimeException(sprintf(
                        "Unable to create the %s directory (%s)\n",
                        $name,
                        $dir
                    ));
                }
            }
            elseif (!is_writable($dir)) {
                throw new \RuntimeException(sprintf(
                    "Unable to write in the %s directory (%s)\n",
                    $name,
                    $dir
                ));
            }
        }

        $container = $this->getContainerBuilder();
        $extensions = array();
        // TODO cache commands

        $container->addObjectResource($this);

        // ensure these extensions are implicitly loaded
        $container->getCompilerPassConfig()->setMergePass(
            new MergeExtensionConfigurationPass($extensions)
        );

        $cont = $this->registerContainerConfiguration(
            $this->getContainerLoader($container)
        );
        if (null !== $cont) {
            $container->merge($cont);
        }

        $container->addCompilerPass(new AddClassesToCachePass($this));
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
        return new ContainerBuilder(new ParameterBag($this->getKernelParameters(
        )));
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
     * Returns a loader for the container.
     *
     * @param ContainerInterface $container The service container
     *
     * @return DelegatingLoader The loader
     */
    protected function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator($this);
        $resolver = new LoaderResolver(array(
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new ClosureLoader($container),
        ));

        return new DelegatingLoader($resolver);
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
    public static function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            }
            elseif (!in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
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

    public function getSugarRoot()
    {
        if ($this->sugarRoot) {
            return $this->sugarRoot;
        }

        try {
            $this->sugarRoot = $this->locateRoot();

        } catch (\Exception $e) {
            // FIXME: replace exception below with
            // re throw Insulin\Console\Exception\SugarRootNotFound if no
            // SugarCRM version found on the path given
            throw $e;
        }

        return $this->sugarRoot;
    }

    public function setSugarRoot($path)
    {
        if (!$this->isSugarRoot($path)) {
            // FIXME: replace exception below with
            // throw Insulin\Console\Exception\InvalidSugarRoot
            throw new \RunTimeException(sprintf(
                "Supplied SugarCRM root '%s' isn't valid",
                $path
            ));
        }

        $this->sugarRoot = $path;
    }

    /**
     * Exhaustive depth-first search to try and locate the Sugar root directory.
     * This makes it possible to run insulin from a subdirectory of the sugar
     * root.
     *
     * @param $startPath
     *   Search start path. Defaults to current working directory.
     *
     * @return
     *   A path to sugar root, or FALSE if not found.
     */
    public function locateRoot($startPath = null)
    {
        $sugarRoot = false;

        $startPath = empty($startPath) ? $this->getCwd() : $startPath;

        foreach (array(true, false) as $follow_symlinks) {
            $path = $startPath;
            if ($follow_symlinks && is_link($path)) {
                $path = realpath($path);
            }
            // Check the start path.
            if ($this->isSugarRoot($path)) {
                $sugarRoot = $path;
                break;
            }
            else {
                // Move up dir by dir and check each.
                while ($path = $this->shiftPathUp($path)) {
                    if ($follow_symlinks && is_link($path)) {
                        $path = realpath($path);
                    }
                    if ($this->isSugarRoot($path)) {
                        $sugarRoot = $path;
                        break 2;
                    }
                }
            }
        }

        if (!$sugarRoot) {
            throw new \RuntimeException('Unable to find a sugar root.');
        }

        return $sugarRoot;
    }

    /**
     * Returns parent directory.
     *
     * @param string
     *   Path to start from.
     *
     * @return string
     *   Parent path of given path.
     */
    protected function shiftPathUp($path)
    {
        if (empty($path)) {
            return false;
        }
        $path = explode('/', $path);
        // Move one directory up.
        array_pop($path);

        return implode('/', $path);
    }

    /**
     * Checks whether given path qualifies as a Sugar root.
     *
     * @param string
     *   Path to check.
     *
     * @return boolean
     *   TRUE if a Sugar root, FALSE otherwise.
     */
    public function isSugarRoot($path)
    {
        if (empty($path) || !is_dir($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid path given: "%s".',
                $path
            ));
        }

        $candidates = array(
            'include/entryPoint.php',
            'include/MVC/SugarApplication.php',
            'config.php',
            'sugar_version.php'
        );
        foreach ($candidates as $candidate) {
            if (!file_exists($path . '/' . $candidate)) {
                return false;
            }
        }

        return true;
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
        // could take us outside of the Sugar root, making it impossible to find.
        // $_SERVER['PWD'] isn't set on windows and generates a Notice.
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

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
        // TODO load other configuration files like in ~/.insulin
    }
}
