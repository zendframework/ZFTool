<?php

namespace ZFTool;

use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use Zend\Mvc\ModuleRouteListener;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ModuleManager\ModuleManagerInterface as ModuleManager;
use Zend\ModuleManager\Listener\ConfigListener as ModuleManagerConfigListener;
use Zend\ModuleManager\ModuleEvent;

class Module implements ConsoleUsageProviderInterface, AutoloaderProviderInterface, ConfigProviderInterface
{
    const VERSION = '0.1';
    const NAME    = 'ZFTool - Zend Framework 2 command line Tool';

    protected $config;

    /**
     * @var ServiceLocatorInterface
     */
    protected $sm;

    public function onBootstrap($e)
    {
        $this->sm = $e->getApplication()->getServiceManager();
    }

    public function getConfig()
    {
        return $this->config = include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getConsoleBanner(ConsoleAdapterInterface $console)
    {
        return self::NAME . ' ver. ' . self::VERSION;
    }

    public function getConsoleUsage(ConsoleAdapterInterface $console)
    {
        if(!empty($this->config->disableUsage)){
            return null; // usage information has been disabled
        }

        // TODO: Load strings from a translation container
        return array(

            'Basic information:',
            'modules [list]'              => 'show loaded modules',
            'version | --version'         => 'display current Zend Framework version',

            'Diagnostics',
            'diag [options] [module name]'  => 'run diagnostics',
            array('[module name]'               , '(Optional) name of module to test'),
            array('-v --verbose'                , 'Display detailed information.'),
            array('-b --break'                  , 'Stop testing on first failure'),
            array('-q --quiet'                  , 'Do not display any output unless an error occurs.'),
            array('--debug'                     , 'Display raw debug info from tests.'),

            'Application configuration:',
            'config [list]'             => 'list all configuration options',
            'config get <name>'         => 'display a single config value, i.e. "config get db.host"',
            'config set <name> <value>' => 'set a single config value (use only to change scalar values)',

            'Project creation:',
            'create project <path>'     => 'create a skeleton application',
            array('<path>', 'The path of the project to be created'),

            'Module creation:',
            'create module <name> [<path>]'     => 'create a module',
            array('<name>', 'The name of the module to be created'),
            array('<path>', 'The root path of a ZF2 application where to create the module'),

            'Classmap generator:',
            'classmap generate <directory> <classmap file> [--append|-a] [--overwrite|-w]' => '',
            array('<directory>',        'The directory to scan for PHP classes (use "." to use current directory)'),
            array('<classmap file>',    'File name for generated class map file  or - for standard output.'.
                                        'If not supplied, defaults to autoload_classmap.php inside <directory>.'),
            array('--append | -a',      'Append to classmap file if it exists'),
            array('--overwrite | -w',   'Whether or not to overwrite existing classmap file'),

            'Zend Framework 2 installation:',
            'install zf <path> [<version>]' => '',
            array('<path>', 'The directory where to install the ZF2 library'),
            array('<version>', 'The version to install, if not specified uses the last available'),
        );
    }

    public function getDiagnostics()
    {
        /* @var $moduleManager ModuleManager */
        $moduleManager = $this->sm->get('modulemanager');
        return array(
            'System time' => function() {
                if(time() < 1365166650) {
                    return new Failure('System clock is not properly set - current time: '.date('r'), time());
                } else {
                    return new Success(date('r'), time());
                }
            },

            'PHP Magic Quotes' => function() {
                if (ini_get('magic_quotes') || ini_get('magic_quotes_gpc') || ini_get('magic_quotes_runtime')) {
                    return new Failure(
                        'Magic Quotes PHP feature is currently enabled. This can lead to unexpected errors in '.
                        'PHP applications. It is recommended that you disable and use proper incoming data '.
                        'and sanitization using Zend\Validator and Zend\Filter. More information on disabling '.
                        'magic quotes can be found at http://www.php.net/manual/pl/security.magicquotes.disabling.php'
                    );
                } else {
                    return new Success('currently disabled.');
                }
            },

            'PHP register_globals' => function() {
                if (ini_get('register_globals')) {
                    return new Failure(
                        'register_globals PHP setting is currently enabled. This can lead to unexpected errors in '.
                        'and is a security threat. It is recommended that you disable this feature by setting '.
                        'register_globals = off in your php.ini file'
                    );
                } else {
                    return new Success('currently disabled.');
                }
            },

            'APC version' => function() {
                if(!$version = phpversion('apc')){
                    return new Success('APC extension not installed');
                }

                // Check buggy version 3.1.14
                // @link http://marc.info/?l=php-internals&m=135996767925762&w=2
                if (version_compare($version, '3.1.14', 'eq')) {
                    return new Failure(
                        'You are using version 3.1.14 which has serious memory issues and has been removed from '.
                        'distribution by its author. More information can be found at '.
                        'https://bugs.php.net/bug.php?id=63909'
                        , $version
                    );
                }

                return new Success('Using APC version ' . $version, $version);
            },

            'Is cache_dir writable' => function() use (&$moduleManager){
                // Try to retrieve MM config listener which contains options
                $cacheDir = false;
                foreach($moduleManager->getEventManager()->getListeners(ModuleEvent::EVENT_LOAD_MODULES) as $listener) {
                    /* @var $listener \Zend\Stdlib\CallbackHandler */
                    $callback = $listener->getCallback();
                    if(
                        is_array($callback) &&
                        isset($callback[0]) &&
                        is_object($callback[0]) &&
                        $callback[0] instanceof ModuleManagerConfigListener
                    ){
                        $options = $callback[0]->getOptions();
                        $cacheDir = $options->getCacheDir();
                        break;
                    }
                }


                if (
                    !$cacheDir ||
                    empty($cacheDir)
                ){
                    return new Warning(
                        'Module listener cache_dir is not configured correctly. Make sure that you have set '.
                        '"cache_dir" option under "module_listener_options" in your application configuration.'
                    );
                }

                if (!file_exists($cacheDir)) {
                    return new Failure(
                        'Module listener cache_dir ('. $cacheDir.') does not exist. Make sure that you have set '.
                        '"cache_dir" option in your application config and that it points to an existing directory.',
                        $cacheDir
                    );
                }

                if (!is_dir($cacheDir)) {
                    return new Failure(
                        'Module listener cache_dir ('. $cacheDir.') is not a directory. Make sure that you have set '.
                        '"cache_dir" option in your application config and that it points to an existing directory.',
                        $cacheDir
                    );
                }

                if (!is_writable($cacheDir)) {
                    return new Failure(
                        'Module listener cache_dir ('. $cacheDir.') is not writable. Make sure that the path, you have '.
                        'set under "cache_dir" in your application config, is writable by your server.',
                        $cacheDir
                    );
                }

                return new Success(
                    'Module listener cache dir exists and is writable: '.$cacheDir,
                    $cacheDir
                );
            }
        );
    }
}
