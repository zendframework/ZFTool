<?php

namespace ZFTool;

use Zend\Mvc\ModuleRouteListener;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;

class Module implements ConsoleUsageProviderInterface, AutoloaderProviderInterface, ConfigProviderInterface
{
    protected $config;

    public function onBootstrap($e)
    {
//        $e->getApplication()->getServiceManager()->get('translator');
//        $eventManager        = $e->getApplication()->getEventManager();
//        $moduleRouteListener = new ModuleRouteListener();
//        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return $this->config = include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConsoleUsage(ConsoleAdapterInterface $console){
        if(!empty($this->config->disableUsage)){
            return null; // usage information has been disabled
        }

        // TODO: Load strings from a translation container
        return array(

            'Basic information:',
            'modules [list]'              => 'show loaded modules',
            'version | --version'         => 'display current Zend Framework version',

            'Application configuration:',
            'config [list]'               => 'list all configuration options',
            'config get <name>'         => 'display a single config value, i.e. "config get db.host"',
            'config set <name> <value>' => 'set a single config value (use only to change scalar values)',

            'Classmap generator:',
            'classmap generate <directory> <classmap file> [--append|-a] [--overwrite|-w]' => '',
            array('<directory>',        'The directory to scan for PHP classes (use "." to use current directory)'),
            array('<classmap file>',    'File name for generated class map file  or - for standard output.'.
                                        'If not supplied, defaults to autoload_classmap.php inside <directory>.'),
            array('--append | -a',      'Append to classmap file if it exists'),
            array('--overwrite | -w',   'Whether or not to overwrite existing classmap file')

        );
    }
}
