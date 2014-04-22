<?php

namespace ZFToolTest;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use Zend\ServiceManager\ServiceLocatorInterface;

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
        return array(
            'test1' => function () {return new Success('test1 success');},
            'test2' => array('is_string', 'a'),
            'test3' => array('stristr', 'abc','d'),
            'test4' => array(__CLASS__,'staticTestMethod'),
            'test5' => array(array(__CLASS__,'staticTestMethod'), 'someOtherMessage'),
        );
    }

    public static function staticTestMethod($message = 'static check message')
    {
        return new Failure($message);
    }
}
