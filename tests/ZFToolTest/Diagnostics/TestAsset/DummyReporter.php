<?php
namespace ZFToolTest\Diagnostics\TestAssets;

use ZendDiagnostics\Runner\Reporter\ReporterInterface;
use ArrayObject;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Collection as ResultsCollection;

class DummyReporter implements ReporterInterface
{
    /**
     * Should onStart return false ?
     *
     * @var bool
     */
    protected $stopAfterRun;

    public function __construct($stopAfterRun = false)
    {
        $this->stopAfterRun = false;
    }

    /**
     * This method is called right after Reporter starts running, via Runner::run()
     *
     * @param  ArrayObject $checks       A collection of Checks that will be performed
     * @param  array       $runnerConfig Complete Runner configuration, obtained via Runner::getConfig()
     * @return void
     */
    public function onStart(ArrayObject $checks, $runnerConfig) {}

    /**
     * This method is called before each individual Check is performed. If this
     * method returns false, the Check will not be performed (will be skipped).
     *
     * @param  CheckInterface $check Check instance that is about to be performed.
     * @param  bool           $alias Test alias.
     * @return bool|void      Return false to prevent check from happening
     */
    public function onBeforeRun(CheckInterface $check, $alias = null) {}

    /**
     * This method is called every time a Check has been performed. If this method
     * returns false, the Runner will not perform any additional checks and stop
     * its run.
     *
     * @param  CheckInterface  $check  A Check instance that has just finished running
     * @param  ResultInterface $result Result for that particular check instance
     * @param  bool            $alias  Test alias.
     * @return bool|void       Return false to prevent from running additional Checks
     */
    public function onAfterRun(CheckInterface $check, ResultInterface $result, $alias = null)
    {
        if (!$this->stopAfterRun) {
            return false;
        }
    }

    /**
     * This method is called when Runner has been aborted and could not finish the
     * whole run().
     *
     * @param  ResultsCollection $results Collection of Results for performed Checks.
     * @return void
     */
    public function onStop(ResultsCollection $results) {}

    /**
     * This method is called when Runner has finished its run.
     *
     * @param  ResultsCollection $results Collection of Results for performed Checks.
     * @return void
     */
    public function onFinish(ResultsCollection $results) {}
}
