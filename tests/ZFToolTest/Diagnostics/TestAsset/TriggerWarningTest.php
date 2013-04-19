<?php
namespace ZFToolTest\Diagnostics\TestAsset;


use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\TestInterface;

class TriggerWarningTest implements TestInterface
{
    protected $label = '';

    protected $result = true;

    public function __construct($result = true)
    {
        $this->result   = $result;
    }

    public function run()
    {
        strpos(); // <-- this will throw a real warning
        return $this->result;
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
