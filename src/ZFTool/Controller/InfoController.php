<?php

namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Version;

class InfoController extends AbstractActionController
{
    public function versionAction()
    {
        return "You are using Zend Framework ".Version::VERSION."\n";
    }

    public function configAction(){

    }
}
