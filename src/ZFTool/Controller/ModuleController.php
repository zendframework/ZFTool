<?php
namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ArrayUtils;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\View\Model\ConsoleModel;
use Zend\Version;
use Zend\Console\ColorInterface as Color;

class ModuleController extends AbstractActionController
{
    public function listAction()
    {
        $console = $this->getServiceLocator()->get('console');
        $modules = $this->getModulesFromService();
        if (empty($modules)) {
            $console->writeLine('No modules installed. Are you in the root folder of a ZF2 app?');
            return;
        }
        $modules = array_diff($modules, array('ZFTool'));

        $console->writeLine("Modules installed:");
        foreach ($modules as $module) {
            $console->writeLine($module, Color::GREEN);
        }
    }

    private function sendError($msg)
    {
        $m = new ConsoleModel();
        $m->setErrorLevel(2);
        $m->setResult($msg . PHP_EOL);
        return $m;
    }

    protected function getModulesFromService()
    {
        $sm = $this->getServiceLocator();
        try{
            /* @var $mm \Zend\ModuleManager\ModuleManager */
            $mm = $sm->get('modulemanager');
        } catch(ServiceNotFoundException $e) {
            return $this->sendError(
                'Cannot get Zend\ModuleManager\ModuleManager instance. Is your application using it?'
            );
        }
        $modules = array_keys($mm->getLoadedModules(false));
       
        return $modules;
    }
}
