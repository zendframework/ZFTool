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
