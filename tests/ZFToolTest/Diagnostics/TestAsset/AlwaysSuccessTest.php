<?php
namespace ZFToolTest\Diagnostics\TestAsset;


use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\AbstractTest;
use ZFTool\Diagnostics\Test\TestInterface;

class AlwaysSuccessTest extends AbstractTest implements TestInterface
{
    protected $label = 'Always Successful Test';

    public function run()
    {
        return new Success();
    }
}
