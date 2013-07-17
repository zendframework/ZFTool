<?php

namespace ZFToolTest;

use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use Zend\Mvc\ModuleRouteListener;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ModuleManager\Listener\ConfigListener as ModuleManagerConfigListener;

class DummyModule
{
    protected $config;

    /**
     * @var ServiceLocatorInterface
     */
    protected $sm;

    public function __construct(ServiceLocatorInterface $sm)
    {
        $this->sm = $sm;
    }

    public function getDiagnostics()
    {
        $moduleManager = $this->sm->get('modulemanager');
        return array(
            'test1' => function() {return new Success('test1 success');},
            'test2' => array('is_string', 'a'),
            'test3' => array('stristr', 'abc','d'),
            'test4' => array(__CLASS__,'staticTestMethod'),
            'test5' => array(array(__CLASS__,'staticTestMethod'), 'someOtherMessage'),
        );
    }

    public static function staticTestMethod($message = 'static test message')
    {
        return new Failure($message);
    }
}
