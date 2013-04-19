<?php
namespace ZFToolTest\Diagnostics\TestAsset;


use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\TestInterface;

class ThrowExceptionTest implements TestInterface
{
    protected $label = '';

    protected $exception;

    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
        $this->label = get_class($exception);
    }

    public function run()
    {
        throw $this->exception;
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
