<?php
namespace ZFToolTest\Diagnostics;

use ZFTool\Diagnostics\Config;
use ZFTool\Diagnostics\Runner;

class RunnerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Runner
     */
    protected $runner;

    public function setUp()
    {
        $this->runner = new Runner();
    }

    public function tearDown()
    {}

    public function testConfig()
    {
        $this->assertInstanceOf('ZFTool\Diagnostics\Config', $this->runner->getConfig());
        $this->assertInstanceOf('ZFTool\Diagnostics\ConfigInterface', $this->runner->getConfig());

        $newConfig = new Config();
        $this->runner->setConfig($newConfig);
        $this->assertInstanceOf('ZFTool\Diagnostics\Config', $this->runner->getConfig());
        $this->assertSame($newConfig, $this->runner->getConfig());

        $newConfig->setBreakOnFailure(true);
        $this->assertTrue($newConfig->getBreakOnFailure());
    }

}
