<?php
namespace ZFToolTest\Diagnostics\TestAssets;

use Zend\Console\Adapter\AbstractAdapter;

class ConsoleAdapter extends AbstractAdapter
{
    public $stream;

    public $autoRewind = true;

    public $testWidth = 80;

    public $testIsUtf8 = true;

    /**
     * Read a single line from the console input
     *
     * @param  int    $maxLength Maximum response length
     * @return string
     */

    /**
     * Force reported width for testing purposes.
     *
     * @param  int $width
     * @return int
     */
    public function setTestWidth($width)
    {
        $this->testWidth = $width;
    }

    /**
     * Force reported utf8 capability.
     *
     * @param bool $isUtf8
     */
    public function setTestUtf8($isUtf8)
    {
        $this->testIsUtf8 = $isUtf8;
    }

    public function isUtf8()
    {
        return $this->testIsUtf8;
    }

    public function getWidth()
    {
        return $this->testWidth;
    }
}
