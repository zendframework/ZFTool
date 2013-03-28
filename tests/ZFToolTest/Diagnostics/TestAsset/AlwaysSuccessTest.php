<?php
namespace ZFToolTest\Diagnostics\TestAsset;


use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\TestInterface;

class AlwaysSuccessTest implements TestInterface
{
    protected $label = 'Always Successful Test';

    public function run()
    {
        return new Success();
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