<?php
namespace ZFTool\Diagnostics\Test;

use ZFTool\Diagnostics\RunEvent;

interface TestInterface
{
    /**
     * @return string|bool
     */
    public function run();

    /**
     * Get test label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Set test label.
     *
     * @param string $label
     * @return void
     */
    public function setLabel($label);
}
