<?php
namespace ZFToolTest\Diagnostics\TestAsset;


use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\TestInterface;

class TriggerUserErrorTest implements TestInterface
{
    protected $label = '';

    protected $message;
    protected $severity;

    protected $result = true;

    public function __construct($message, $severity, $result = true)
    {
        $this->message  = $message;
        $this->severity = $severity;
        $this->result   = $result;
        $this->label    = 'error severity '.$severity;
    }

    public function run()
    {
        trigger_error($this->message, $this->severity);
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
