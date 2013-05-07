<?php
namespace ZFToolTest\Diagnostics\Reporter;

use ZFTool\Diagnostics\RunEvent;
use ZFTool\Diagnostics\Runner;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest;
use ZFToolTest\Diagnostics\TestAssets\DummyReporter;

require_once __DIR__.'/../TestAsset/DummyReporter.php';

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
        $test = new AlwaysSuccessTest();
        $runner->addTest($test);
        $runner->run();
    }

    public function testDummyReporterStopped()
    {
        $reporter = new DummyReporter();
        $runner = new Runner();
        $runner->addReporter($reporter);
        $test = new AlwaysSuccessTest();
        $runner->addTest($test);
        $runner->getEventManager()->attach(RunEvent::EVENT_AFTER_RUN, function(){
            return false;
        });
        $runner->run();
    }
}
