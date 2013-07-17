<?php
namespace ZFTool\Diagnostics;

use Zend\Stdlib\AbstractOptions;
use \Traversable;

class Config extends AbstractOptions implements ConfigInterface
{
    /**
     * @var bool
     */
    protected $breakOnFailure = false;

    /**
     * @var string
     */
    protected $defaultRunListenerClass = '\ZFTool\Diagnostics\RunListener';

    /**
     * Should diagnostics stop on first failure.
     *
     * @param boolean $breakOnFailure
     */
    public function setBreakOnFailure($breakOnFailure)
    {
        $this->breakOnFailure = $breakOnFailure;
    }

    /**
     * @return boolean
     */
    public function getBreakOnFailure()
    {
        return $this->breakOnFailure;
    }

    /**
     * Set the default class to create run listener.
     *
     * @param string $defaultRunListenerClass
     */
    public function setDefaultRunListenerClass($defaultRunListenerClass)
    {
        $this->defaultRunListenerClass = $defaultRunListenerClass;
    }

    /**
     * @return string
     */
    public function getDefaultRunListenerClass()
    {
        return $this->defaultRunListenerClass;
    }


}
