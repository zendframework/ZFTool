<?php

namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\ArrayUtils;
use Zend\Version\Version;
use Zend\Console\ColorInterface as Color;
use Zend\Config\Writer\Ini as IniWriter;
use ZFTool\Model\Config;
use ZFTool\Module;

class ConfigController extends AbstractActionController
{

    public function listAction()
    {
        $console = $this->getServiceLocator()->get('console');
        $sm = $this->getServiceLocator();

        $isLocal = $this->params()->fromRoute('local');

        if ($isLocal) {
            $appdir = getcwd();
            echo $appdir;
            if (file_exists($appdir . '/config/autoload/local.php')) {
                $config = include $appdir . '/config/autoload/local.php';
            } else {
                echo 'FILE NO EXIST' . PHP_EOL;
                $config = array();
            }
        } else {
            $config = $sm->get('Configuration');
        }

        if (!is_array($config)){
            $config = ArrayUtils::iteratorToArray($config, true);
        }

        $console->writeLine('Configuration:', Color::GREEN);
        // print_r($config);
        $ini = new IniWriter;
        echo $ini->toString($config);
    }

    public function getAction()
    {
        $console = $this->getServiceLocator()->get('console');
        $sm = $this->getServiceLocator();

        $name = $this->params()->fromRoute('arg1');

        if (!$name) {
            $console->writeLine('config get <name> was not provided', Color::RED);
            return;
        }

        $isLocal = $this->params()->fromRoute('local');

        if ($isLocal) {
            $appdir = getcwd();
            $configFile = new Config($appdir . '/config/autoload/local.php');
            $configFile->read($name);
        } else {
            $config = $sm->get('Configuration');
            echo $name;
            $value =  Config::findValueInArray($name, $config);
            if (is_scalar($value)) {
                echo ' = ' . $value;
            } else {
                echo ':' . PHP_EOL;
                var_export($value);
            }
            echo PHP_EOL;
        }
    }

    public function setAction()
    {
        $console = $this->getServiceLocator()->get('console');
        $sm = $this->getServiceLocator();

        $name = $this->params()->fromRoute('arg1');
        $value = $this->params()->fromRoute('arg2');

        if ($value === 'null') {
            $value = null;
        }

        $appdir = getcwd();
        $configPath = $appdir . '/config/autoload/local.php';
        $configFile = new Config($configPath);
        $configFile->write($name, $value);

        echo 'Config file written at: ' . $configPath . PHP_EOL;
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
