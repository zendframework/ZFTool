<?php
namespace ZFTool\Diagnostics\Test;

abstract class AbstractTest implements TestInterface
{
    protected $label;

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }
}
