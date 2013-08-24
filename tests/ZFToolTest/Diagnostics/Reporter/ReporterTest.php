<?php
namespace ZFToolTest\Diagnostics\Reporter;

use ZFTool\Diagnostics\Runner;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessCheck;
use ZFToolTest\Diagnostics\TestAssets\DummyReporter;

require_once __DIR__.'/../TestAsset/DummyReporter.php';
require_once __DIR__.'/../TestAsset/AlwaysSuccessCheck.php';

class ReporterTest extends \PHPUnit_Framework_TestCase
{
    public function testReporterAttaching()
    {
        $reporter = new DummyReporter();
        $runner = new Runner();
        $runner->addReporter($reporter);
        $runner->removeReporter($reporter);
    }

    public function testDummyReporterStandardRun()
    {
        $reporter = new DummyReporter();
        $runner = new Runner();
        $runner->addReporter($reporter);
        $check = new AlwaysSuccessCheck();
        $runner->addCheck($check);
        $runner->run();
    }

    public function testDummyReporterStopped()
    {
        $reporter = new DummyReporter(true);
        $runner = new Runner();
        $runner->addReporter($reporter);
        $check1 = new AlwaysSuccessCheck();
        $check2 = new AlwaysSuccessCheck();
        $runner->addCheck($check1);
        $runner->addCheck($check2);
        $result = $runner->run();
        $this->assertFalse($result->offsetExists($check2));
    }
}
