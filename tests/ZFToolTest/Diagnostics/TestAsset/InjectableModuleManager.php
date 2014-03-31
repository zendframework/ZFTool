<?php

namespace ZFToolTest\TestAsset;

use Zend\ModuleManager\ModuleManager;

/**
 * Module manager
 */
class InjectableModuleManager extends ModuleManager
{
    public function __construct() {}

    public function injectModule($name, $module)
    {
        $this->loadedModules[$name] = $module;
    }
}
