<?php
namespace ZFToolTest\Diagnostics\Reporter;


use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use ZFTool\Diagnostics\Result\Unknown;
use ZFTool\Diagnostics\RunEvent;
use ZFTool\Diagnostics\RunListener;
use ZFToolTest\Diagnostics\TestAsset\ReturnThisTest;
use ZFToolTest\Diagnostics\TestAsset\ThrowExceptionTest;
use ZFToolTest\Diagnostics\TestAsset\TriggerUserErrorTest;
use ZFToolTest\Diagnostics\TestAsset\TriggerWarningTest;
use Zend\EventManager\EventManager;

require_once __DIR__.'/TestAsset/ReturnThisTest.php';
require_once __DIR__.'/TestAsset/ThrowExceptionTest.php';
require_once __DIR__.'/TestAsset/TriggerUserErrorTest.php';
require_once __DIR__.'/TestAsset/TriggerWarningTest.php';

class RunListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ZFTool\Diagnostics\RunListener
     */
    protected $listener;

    /**
     * @var \Zend\EventManager\EventManager;
     */
    protected $em;

    protected $oldPHPUnitNoticeSetting;
    protected $oldPHPUnitWarningSetting;

    public function setUp()
    {
        $this->em = new EventManager();
        $this->listener = new RunListener();
        $this->em->attachAggregate($this->listener);

        $this->oldPHPUnitNoticeSetting = \PHPUnit_Framework_Error_Notice::$enabled;
        $this->oldPHPUnitWarningSetting = \PHPUnit_Framework_Error_Warning::$enabled;
    }

    public function tearDown()
    {
        \PHPUnit_Framework_Error_Notice::$enabled = $this->oldPHPUnitNoticeSetting;
        \PHPUnit_Framework_Error_Warning::$enabled = $this->oldPHPUnitWarningSetting;

        $this->em->detachAggregate($this->listener);
        unset($this->em, $this->listener);
    }

    public function testPassThroughResults()
    {
        $e = new RunEvent();

        $expectedResult = new Success('foo');
        $test = new ReturnThisTest($expectedResult);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertSame($expectedResult, $result);

        $expectedResult = new Failure();
        $test = new ReturnThisTest($expectedResult);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertSame($expectedResult, $result);

        $expectedResult = new Warning();
        $test = new ReturnThisTest($expectedResult);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertSame($expectedResult, $result);

        $expectedResult = new Unknown();
        $test = new ReturnThisTest($expectedResult);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertSame($expectedResult, $result);
    }

    public function testInterpretBooleanResult()
    {
        $e = new RunEvent();

        $test = new ReturnThisTest(true);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $result);

        $test = new ReturnThisTest(false);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
    }

    public function testInterpretScalarAsWarning()
    {
        $e = new RunEvent();

        $test = new ReturnThisTest('something went wrong');
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Warning', $result);
        $this->assertEquals('Test returned unexpected string', $result->getMessage());
        $this->assertEquals('something went wrong', $result->getData());

        $test = new ReturnThisTest(100000);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Warning', $result);
        $this->assertEquals('Test returned unexpected integer', $result->getMessage());
        $this->assertEquals('100000', $result->getData());
    }

    public function testInterpretUnknownAsFailure()
    {
        $e = new RunEvent();

        // a resource
        $res = fopen('php://memory', 'r');
        $test = new ReturnThisTest($res);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
        $this->assertSame($res, $result->getData());
        fclose($res);

        // unknown object
        $obj = new \stdclass;
        $test = new ReturnThisTest($obj);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
        $this->assertSame($obj, $result->getData());
    }

    public function testCatchExceptions()
    {
        $e = new RunEvent();
        $exception = new \Exception('foo');
        $test = new ThrowExceptionTest($exception);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
        $this->assertStringStartsWith('Uncaught '.get_class($exception), $result->getMessage());
        $this->assertSame($exception, $result->getData());
    }

    public function testCatchErrors()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        \PHPUnit_Framework_Error_Notice::$enabled  = false;

        $e = new RunEvent();
        $this->listener->setCatchErrorSeverity(E_WARNING);
        $test = new TriggerWarningTest();
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
        $this->assertInstanceOf('ErrorException', $result->getData());

        $e = new RunEvent();
        $this->listener->setCatchErrorSeverity(E_WARNING|E_USER_ERROR);
        $test = new TriggerUserErrorTest('bar', E_USER_ERROR);
        $e->setTarget($test);
        $result = $this->em->trigger(RunEvent::EVENT_RUN, $e)->last();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
        $this->assertStringStartsWith('PHP USER_ERROR: bar', $result->getMessage());
        $this->assertInstanceOf('ErrorException', $result->getData());
    }

    public function testSeverityDescriptions()
    {
        for($x = 0; $x <= 14; $x++){
            $severity = pow(2, $x);
            $this->assertNotNull($description = RunListener::getSeverityDescription($severity));

            $constantName = 'E_'.$description;
            $this->assertTrue(defined($constantName));
            $this->assertSame(constant($constantName), $severity);
        }

        $this->assertSame('error severity 999', RunListener::getSeverityDescription(999));
    }



}
