<?php
namespace ZFToolTest\Diagnostics\TestAsset;


use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\TestInterface;

class ReturnThisTest implements TestInterface
{
    protected $label = '';

    protected $value;

    public function __construct($valueToReturn)
    {
        $this->value = $valueToReturn;
        $this->label = gettype($valueToReturn);
    }

    public function run()
    {
        return $this->value;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }
}
