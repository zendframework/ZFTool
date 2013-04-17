<?php

namespace ZFToolTest\TestAsset;

use Traversable;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\ModuleManager;

/**
 * Module manager
 */
class InjectableModuleManager extends ModuleManager
{
    public function __construct(){}

    public function injectModule($name, $module)
    {
        $this->loadedModules[$name] = $module;
    }
}
