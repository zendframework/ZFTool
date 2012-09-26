<?php

namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ArrayUtils;
use Zend\Version\Version;


class InfoController extends AbstractActionController
{
    public function versionAction()
    {
        return "You are using Zend Framework ".Version::VERSION."\n";
    }

    public function configAction(){
        $sm = $this->getServiceLocator();
        $config = $sm->get('Configuration');

        if(!is_array($config)){
            $config = ArrayUtils::iteratorToArray($config, true);
        }

        return print_r($config, true);
    }


}
