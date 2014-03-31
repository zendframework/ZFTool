<?php
namespace ZFToolTest\Diagnostics\TestAsset;

use ZendDiagnostics\Check\CheckInterface;

class ReturnThisCheck implements CheckInterface
{
    protected $label = '';

    protected $value;

    public function __construct($valueToReturn)
    {
        $this->value = $valueToReturn;
        $this->label = gettype($valueToReturn);
    }

    public function check()
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
