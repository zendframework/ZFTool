<?php
namespace ZFToolTest\Diagnostics;

use ZFTool\Diagnostics\Config;
use ZFTool\Diagnostics\Reporter\BasicConsole;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Unknown;
use ZFTool\Diagnostics\Result\Warning;
use ZFTool\Diagnostics\RunEvent;
use ZFTool\Diagnostics\Runner;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest;
use ZFToolTest\Diagnostics\TestAsset\ReturnThisTest;
use ZFToolTest\Diagnostics\TestAsset\ThrowExceptionTest;
use ZFToolTest\Diagnostics\TestAsset\TriggerUserErrorTest;
use ZFToolTest\Diagnostics\TestAsset\TriggerWarningTest;
use ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter;
use Zend\EventManager\EventManager;

require_once __DIR__.'/TestAsset/ReturnThisTest.php';
require_once __DIR__.'/TestAsset/ThrowExceptionTest.php';
require_once __DIR__.'/TestAsset/TriggerUserErrorTest.php';
require_once __DIR__.'/TestAsset/TriggerWarningTest.php';
require_once __DIR__.'/TestAsset/AlwaysSuccessTest.php';
require_once __DIR__.'/TestAsset/ConsoleAdapter.php';

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

    public function testsAndResultsProvider()
    {
        return array(
            array(
                $success = new Success(),
                $success,
            ),
            array(
                $warning = new Warning(),
                $warning,
            ),
            array(
                $failure = new Failure(),
                $failure,
            ),
            array(
                $unknown = new Unknown(),
                $unknown,
            ),
            array(
                true,
                'ZFTool\Diagnostics\Result\Success'
            ),
            array(
                false,
                'ZFTool\Diagnostics\Result\Failure'
            ),
            array(
                null,
                'ZFTool\Diagnostics\Result\Failure',
            ),
            array(
                new \stdClass(),
                'ZFTool\Diagnostics\Result\Failure',
            ),
            array(
                'abc',
                'ZFTool\Diagnostics\Result\Warning',
            ),
        );
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

        $newConfig->setDefaultRunListenerClass('\stdClass');
        $this->assertSame('\stdClass', $newConfig->getDefaultRunListenerClass());

        $em = new EventManager();
        $this->runner->setEventManager($em);
        $this->assertSame($em, $this->runner->getEventManager());
    }

    public function testManagingTests()
    {
        $test1 = new AlwaysSuccessTest();
        $test2 = new AlwaysSuccessTest();
        $test3 = new AlwaysSuccessTest();
        $this->runner->addTest($test1);
        $this->runner->addTests(array(
            $test2,
            $test3
        ));
        $this->assertContains($test1, $this->runner->getTests());
        $this->assertContains($test2, $this->runner->getTests());
        $this->assertContains($test3, $this->runner->getTests());
    }

    public function testAddInvalidtest()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->runner->addTests(array( new \stdClass()));
    }

    public function testAddWrongParam()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->runner->addTests('foo');
    }

    public function testRunEventWrongTarget()
    {
        $e = new RunEvent();
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $e->setTarget(new \stdClass());
    }

    public function testAddReporter()
    {
        $reporter = new BasicConsole(new ConsoleAdapter());
        $this->runner->addReporter($reporter);
        $found = false;
        foreach($this->runner->getEventManager()->getListeners(RunEvent::EVENT_AFTER_RUN) as $l){
            /* @var $l \Zend\Stdlib\CallbackHandler */
            $callback = $l->getCallback();
            if(is_array($callback) && isset($callback[0]) && $callback[0] === $reporter){
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testStart()
    {
        $eventFired = false;
        $self = $this;
        $test = new AlwaysSuccessTest();
        $this->runner->addTest($test);
        $this->runner->getEventManager()->attach(RunEvent::EVENT_START, function(RunEvent $e) use (&$self, &$test, &$eventFired){
            $eventFired = true;
            $self->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $e->getResults());
            $self->assertNull($e->getLastResult());
            $self->assertContains($test, $e->getParam('tests'));
        });
        $this->runner->run();
        $this->assertTrue($eventFired);
    }

    public function testBeforeRun()
    {
        $eventFired = false;
        $self = $this;
        $test = new AlwaysSuccessTest();
        $this->runner->addTest($test);
        $this->runner->getEventManager()->attach(RunEvent::EVENT_BEFORE_RUN, function(RunEvent $e) use (&$self, &$test, &$eventFired){
            $eventFired = true;
            $self->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $e->getResults());
            $self->assertNull($e->getLastResult());
            $self->assertSame($test, $e->getTarget());
            $self->assertContains($test, $e->getParam('tests'));
        });
        $this->runner->run();
        $this->assertTrue($eventFired);
    }

    public function testRunEvent()
    {
        $eventFired = false;
        $self = $this;
        $test = new AlwaysSuccessTest();
        $this->runner->addTest($test);
        $this->runner->getEventManager()->attach(RunEvent::EVENT_RUN, function(RunEvent $e) use (&$self, &$test, &$eventFired){
            $eventFired = true;
            $self->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $e->getResults());
            $self->assertNull($e->getLastResult());
            $self->assertSame($test, $e->getTarget());
            $self->assertContains($test, $e->getParam('tests'));
            return new Success();
        }, 100);
        $this->runner->run();
        $this->assertTrue($eventFired);
    }

    public function testRunListenerWithUnknownResult()
    {
        $eventFired = false;
        $self = $this;
        $test = new AlwaysSuccessTest();
        $this->runner->addTest($test);
        $this->runner->getEventManager()->clearListeners(RunEvent::EVENT_RUN);
        $this->runner->getEventManager()->attach(RunEvent::EVENT_RUN, function(RunEvent $e) use (&$self, &$test, &$eventFired){
            return 'foo';
        }, 100);
        $this->setExpectedException('ZFTool\Diagnostics\Exception\RuntimeException');
        $this->runner->run();
    }

    public function testSummaryWithWarnings()
    {
        $reporter = new BasicConsole(new ConsoleAdapter());
        $this->runner->addReporter($reporter);

        $tests = array();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new ReturnThisTest(new Warning());
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new ReturnThisTest(new Unknown());
        }

        $this->runner->addTests($tests);

        ob_start();

        $this->runner->run();

        $this->assertStringMatchesFormat('%A5 warnings, 15 successful tests, 5 unknown test results%A', trim(ob_get_clean()));
    }

    public function testSummaryWithFailures()
    {
        $reporter = new BasicConsole(new ConsoleAdapter());
        $this->runner->addReporter($reporter);

        $tests = array();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new ReturnThisTest(new Warning());
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new ReturnThisTest(new Unknown());
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new ReturnThisTest(new Failure());
        }

        $this->runner->addTests($tests);

        ob_start();
        \PHPUnit_Framework_Error_Notice::$enabled  = false;
        $this->runner->run();

        $this->assertStringMatchesFormat(
            '%A5 failures, 5 warnings, 15 successful tests, 5 unknown test results%A',
            trim(ob_get_clean())
        );
    }


    /**
     * @dataProvider testsAndResultsProvider
     */
    public function testStandardResults($value, $expectedResult)
    {
        $test = new ReturnThisTest($value);
        $this->runner->addTest($test);
        $results = $this->runner->run();

        if(is_string($expectedResult)){
            $this->assertInstanceOf($expectedResult, $results[$test]);
        } else {
            $this->assertSame($expectedResult, $results[$test]);
        }
    }

    public function testExceptionResultsInFailure()
    {
        $exception = new \Exception();
        $test = new ThrowExceptionTest($exception);
        $this->runner->addTest($test);
        $results = $this->runner->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
    }

    public function testPHPWarningResultsInFailure()
    {
        $test = new TriggerWarningTest();
        $this->runner->addTest($test);
        $results = $this->runner->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
        $this->assertInstanceOf('ErrorException', $results[$test]->getData());
        $this->assertEquals(E_WARNING, $results[$test]->getData()->getSeverity());
    }

    public function testPHPUserErrorResultsInFailure()
    {
        $test = new TriggerUserErrorTest('error', E_USER_ERROR);
        $this->runner->addTest($test);
        $results = $this->runner->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
        $this->assertInstanceOf('ErrorException', $results[$test]->getData());
        $this->assertEquals(E_USER_ERROR, $results[$test]->getData()->getSeverity());
    }

    public function testBreakOnFirstFailure()
    {
        $eventFired = false;
        $self = $this;
        $test = new ReturnThisTest(false);
        $this->runner->getConfig()->setBreakOnFailure(true);
        $this->runner->addTest($test);
        $this->runner->getEventManager()->attach(RunEvent::EVENT_STOP, function(RunEvent $e) use (&$self, &$test, &$eventFired){
            $eventFired = true;
            $self->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $e->getResults());
            $self->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $e->getLastResult());
            $self->assertSame($test, $e->getTarget());
        });

        $results = $this->runner->run();
        $self->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
        $this->assertTrue($eventFired);
    }

    public function testBeforeRunSkipTest()
    {
        $eventFired = false;
        $self = $this;
        $test1 = new AlwaysSuccessTest();
        $test2 = new ReturnThisTest(new Failure());
        $this->runner->addTest($test1);
        $this->runner->addTest($test2);
        $this->runner->getEventManager()->attach(RunEvent::EVENT_BEFORE_RUN, function(RunEvent $e) use (&$self, &$test1, &$eventFired){
            $eventFired = true;
            $self->assertInstanceOf('ZFTool\Diagnostics\Test\TestInterface', $e->getTarget());

            // skip the first test
            if($e->getTarget() === $test1){
                return false;
            }
        });

        $results = $this->runner->run();
        $this->assertTrue($eventFired);
        $this->assertNotContains($test1, $results);
        $this->assertNotNull($results[$test2]);
    }

    public function testAfterRunStopTesting()
    {
        $eventFired = false;
        $eventFired2 = false;
        $self = $this;
        $test1 = new AlwaysSuccessTest();
        $test2 = new ReturnThisTest(new Failure());
        $this->runner->addTest($test1);
        $this->runner->addTest($test2);
        $this->runner->getEventManager()->attach(RunEvent::EVENT_AFTER_RUN, function(RunEvent $e) use (&$self, &$test1, &$eventFired){
            $eventFired = true;
            $self->assertInstanceOf('ZFTool\Diagnostics\Test\TestInterface', $e->getTarget());

            // stop testing after first test
            if($e->getTarget() === $test1){
                return false;
            }
        });

        $this->runner->getEventManager()->attach(RunEvent::EVENT_STOP, function(RunEvent $e) use (&$self, &$test1, &$eventFired2){
            $eventFired2 = true;
            $self->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $e->getResults());
            $self->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $e->getLastResult());
            $self->assertSame($test1, $e->getTarget());
        });

        $results = $this->runner->run();
        $this->assertTrue($eventFired);
        $this->assertTrue($eventFired2);
        $this->assertNotNull($results[$test1]);
        $this->assertNotContains($test2, $results);
    }



}
