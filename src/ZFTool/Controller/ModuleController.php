<?php

namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ArrayUtils;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\View\Model\ConsoleModel;
use Zend\Version;


class ModuleController extends AbstractActionController
{
    public function listAction(){
        $sm = $this->getServiceLocator();
        try{
            /* @var $mm \Zend\ModuleManager\ModuleManager */
            $mm = $sm->get('modulemanager');
        }catch(ServiceNotFoundException $e){
            $m = new ConsoleModel();
            $m->setErrorLevel(1);
            $m->setResult('Cannot get Zend\ModuleManager\ModuleManager instance. Is your application using it?');
            return $m;
        }

        return print_r(array_keys($mm->getLoadedModules(false)),true);
    }


}
