<?php

namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ArrayUtils;
use Zend\Version\Version;
use Zend\Console\ColorInterface as Color;
use ZFTool\Module;

class InfoController extends AbstractActionController
{

    public function versionAction()
    {
        $console = $this->getServiceLocator()->get('console');

        $zf2Path = $this->getZF2Path();
        if (file_exists($zf2Path . '/Zend/Version/Version.php')) {
            require_once $zf2Path . '/Zend/Version/Version.php';
            $msg = 'The application in this folder is using Zend Framework ';
        } else {
            $msg = 'The ZFTool is using Zend Framework ';
        }

        $console->writeLine(Module::NAME, Color::GREEN);
        $console->writeLine($msg . Version::VERSION);
    }

    public function configAction()
    {
        $console = $this->getServiceLocator()->get('console');

        $sm = $this->getServiceLocator();
        $config = $sm->get('Configuration');

        if(!is_array($config)){
            $config = ArrayUtils::iteratorToArray($config, true);
        }
        $console->writeLine ('Configuration:', Color::GREEN);
        print_r($config);
    }

    protected function getZF2Path()
    {
        if (getenv('ZF2_PATH')) {
            return getenv('ZF2_PATH');
        } elseif (get_cfg_var('zf2_path')) {
            return get_cfg_var('zf2_path');
        } elseif (is_dir('vendor/ZF2/library')) {
            return 'vendor/ZF2/library';
        } elseif (is_dir('vendor/zendframework/zendframework/library')) {
            return 'vendor/zendframework/zendframework/library';
        } elseif (is_dir('vendor/zendframework/zend-version')) {
            return 'vendor/zendframework/zend-version';
        }
        return false;
    }

}
