<?php
namespace ZFToolTest\Diagnostics;

use ZFTool\Diagnostics\Config;
use ZFTool\Diagnostics\Runner;

require_once __DIR__.'/TestAsset/UnknownResult.php';

/**
 * Class RunnerTest
 *
 * @see ZendDiagnostics\Runner\Runner
 * @see ZendDiagnosticsTest\RunnerTest
 */
class RunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Runner
     */
    protected $runner;

    public function setUp()
    {
        $this->runner = new Runner(array());
    }

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
        $this->assertTrue($this->runner->getBreakOnFailure());

        $this->runner->setBreakOnFailure(false);
        $this->assertFalse($this->runner->getBreakOnFailure());
        $this->assertFalse($newConfig->getBreakOnFailure());

        $newConfig->setCatchErrorSeverity(100);
        $this->assertEquals(100, $newConfig->getCatchErrorSeverity());
        $this->assertEquals(100, $this->runner->getCatchErrorSeverity());

        $this->runner->setCatchErrorSeverity(200);
        $this->assertEquals(200, $newConfig->getCatchErrorSeverity());
        $this->assertEquals(200, $this->runner->getCatchErrorSeverity());

        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->runner->setConfig('foo');
    }
}
