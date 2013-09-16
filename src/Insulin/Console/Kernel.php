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
    /**
     * Cached Insulin root dir.
     *
     * @var string
     * @see Kernel::getRootDir() where it is being cached.
     */
    protected $rootDir;

    /**
     * True if we are running Kernel in debug not, false otherwise.
     *
     * @var bool
     */
    protected $debug;

    /**
     * True if kernel is initialized and ready to boot, false otherwise.
     *
     * @var bool
     */
    protected $initialized;

    /**
     * True when the Kernel is booted up, false otherwise.
     *
     * @var bool
     */
    protected $booted;

    /**
     * The level of boot that we are currently.
     *
     * @var int
     *
     * @see KernelInterface for a list of possible `BOOT_*` levels.
     */
    protected $bootedLevel;

    /**
     * @var float The start timestamp when Kernel was created.
     */
    protected $startTime;

    /**
     * Cache classes used in this Kernel.
     *
     * @var array
     */
    protected $classes;

    /**
     * The Sugar path found when booting level `BOOT_SUGAR_ROOT`.
     *
     * @var string
     */
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
     * @param bool $debug
     *   Whether to enable debugging or not.
     *
     * @api
     */
    public function __construct($debug = false)
    {
        $this->debug = (bool) $debug;
        $this->booted = false;
        $this->classes = array();

        if ($this->debug) {
            $this->startTime = microtime(true);
        }

        if ($this->debug) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        } else {
            ini_set('display_errors', 0);
        }
    }

    /**
     * Override default php clone method to reset the kernel state when cloned.
     */
    public function __clone()
    {
        if ($this->debug) {
            $this->startTime = microtime(true);
        }

        $this->booted = false;
        $this->container = null;
    }

    /**
     * {@inheritdoc}
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
        if ($this->booted) {
            return;
        }

        $this->initialize();
        $this->container->get('dispatcher')->addSubscriber(new BootSubscriber());

        $bootedLevel = 0;
        $previousException = null;
        try {
            $levels = $this->getBootstrapLevels();
            foreach ($levels as $level) {
                $this->bootTo($level);
                $bootedLevel = $level;
            }

        } catch (\Exception $e) {
            $previousException = $e;
        }

        $this->booted = $bootedLevel > 0;

        if (!$this->booted) {
            $this->container->get('dispatcher')->dispatch(
                KernelEvents::BOOT_FAILURE,
                new KernelBootEvent($this, $previousException)
            );
            throw $previousException;
        }

        $this->bootedLevel = $bootedLevel;

        $this->container->get('dispatcher')->dispatch(
            KernelEvents::BOOT_SUCCESS,
            new KernelBootEvent($this)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBootedLevel()
    {
        return $this->isBooted() ? $this->bootedLevel : false;
    }

    /**
     * Get a list of the default bootstrap levels that we want to boot to by
     * default.
     *
     * @return array
     *   A list of standard bootstrap levels to boot.
     */
    public function getBootstrapLevels()
    {
        static $levels = array(
            self::BOOT_INSULIN,
            self::BOOT_SUGAR_ROOT,
            self::BOOT_SUGAR_CONFIGURATION,
            self::BOOT_SUGAR_DATABASE,
            self::BOOT_SUGAR_FULL,
            self::BOOT_SUGAR_LOGIN,
        );

        return $levels;
    }

    /**
     * Wrapper to boot up to a given level.
     *
     * This will try to boot the Kernel and will throw an Exception if unable
     * to boot to the given level.
     * These exceptions are thrown by the functions that we are wrapping.
     *
     * @param int $level
     *   The level to boot to.
     *
     * FIXME: create KernelBootLevelException
     * @throws \Exception on boot failure for given level.
     */
    protected function bootTo($level)
    {
        $this->container->get('dispatcher')->dispatch(
            KernelEvents::BOOT_LEVEL_BEFORE,
            new KernelBootLevelEvent($level, $this)
        );

        try {
            $this->container->get('dispatcher')->dispatch(
                KernelEvents::BOOT_LEVEL,
                new KernelBootLevelEvent($level, $this)
            );

        } catch (\Exception $e) {
            $this->container->get('dispatcher')->dispatch(
                KernelEvents::BOOT_LEVEL_FAILURE,
                new KernelBootLevelEvent($level, $this, $e)
            );

            throw $e;
        }

        $this->container->get('dispatcher')->dispatch(
            KernelEvents::BOOT_LEVEL_SUCCESS,
            new KernelBootLevelEvent($level, $this)
        );
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this::VERSION;
    }

    /**
     * {@inheritdoc}
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getStartTime()
    {
        return $this->debug ? $this->startTime : -INF;
    }

    /**
     * Protects a list of given directories from web access.
     *
     * @param string|array $dirs
     *   A directory path or an array of directories to protect from web
     *   access.
     */
    protected function protectFromWebAccess($dirs)
    {
        foreach ((array) $dirs as $dir) {
            if (!file_exists($dir . '/.htaccess')) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                file_put_contents($dir . '/.htaccess', 'Deny from all');
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * The location defaults to `~/.insulin/` on *nix and
     * `%LOCALAPPDATA%\Insulin\` on windows.
     * When getting the home directory, we also protect it against web access,
     * since HOME can be the www-data's user home and be web-accessible.
     *
     * @throws \RuntimeException if unable to get Insulin's home folder.
     */
    public function getHomeDir()
    {
        $home = getenv('INSULIN_HOME');
        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (!getenv('APPDATA')) {
                    throw new \RuntimeException(
                        'The APPDATA or INSULIN_HOME environment variable must be set for insulin to run correctly'
                    );
                }
                $home = $this->convertPath(getenv('APPDATA')) . '/Insulin';
            } else {
                if (!getenv('HOME')) {
                    throw new \RuntimeException(
                        'The HOME or INSULIN_HOME environment variable must be set for insulin to run correctly'
                    );
                }
                $home = rtrim(getenv('HOME'), '/') . '/.insulin';
            }
        }

        $this->protectFromWebAccess($home);

        return $home;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        $cacheDir = getenv('COMPOSER_CACHE_DIR');

        if (!$cacheDir) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if ($cacheDir = getenv('LOCALAPPDATA')) {
                    $cacheDir .= '/Composer';
                } else {
                    $cacheDir = $this->getHomeDir() . '/cache';
                }
                $cacheDir = strtr($cacheDir, '\\', '/');

            } else {
                $cacheDir = $this->getHomeDir() . '/cache';
            }
        }

        $this->protectFromWebAccess($cacheDir);

        return $cacheDir;
    }

    /**
     * {@inheritdoc}
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
        $this->container->set('sugar_manager', new \Insulin\Sugar\Manager());

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
     *   An array of kernel parameters.
     */
    protected function getKernelParameters()
    {
        return array_merge(
            array(
                'kernel.root_dir' => $this->rootDir,
                'kernel.debug' => $this->debug,
                'kernel.name' => self::NAME,
                'kernel.cache_dir' => $this->getCacheDir(),
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
     *   An array of parameters.
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
        );
        foreach ($dirs as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true)) {
                    throw new \RuntimeException(
                        sprintf(
                            "Unable to create the '%s' directory (%s).",
                            $name,
                            $dir
                        )
                    );
                }
            } elseif (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        "Unable to write in the '%s' directory (%s)",
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

    /**
     * Provide a way to serialize a kernel. Currently save the debug status.
     *
     * @return string
     *   The serialized Kernel.
     */
    public function serialize()
    {
        return serialize(array($this->debug));
    }

    /**
     * When restoring a Kernel use the serialized state to create a new
     * instance.
     *
     * @param string $data
     *   The serialized data.
     */
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
        // find. $_SERVER['PWD'] isn't set on windows and generates a Notice.
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
     * {@inheritdoc}
     */
    public function getSugarPath()
    {
        return $this->sugarPath;
    }

    /**
     * {@inheritdoc}
     */
    public function setSugarPath($path)
    {
        $this->sugarPath = $path;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->container->get($id);
    }
}
