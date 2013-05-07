<?php
namespace ZFToolTest\Diagnostics\Reporter;

use ZFTool\Diagnostics\Reporter\VerboseConsole;
use ZFTool\Diagnostics\Result\Collection;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use ZFTool\Diagnostics\Result\Unknown;
use ZFTool\Diagnostics\RunEvent;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest;
use ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter;
use ZFToolTest\Diagnostics\TestAssets\DummyReporter;
use Zend\Console\Charset\Ascii;
use Zend\EventManager\EventManager;

require_once __DIR__.'/../TestAsset/AlwaysSuccessTest.php';
require_once __DIR__.'/../TestAsset/ConsoleAdapter.php';

class VerboseConsoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter
     */
    protected $console;

    /**
     * @var \ZFTool\Diagnostics\Reporter\VerboseConsole
     */
    protected $reporter;

    /**
     * @var \Zend\EventManager\EventManager;
     */
    protected $em;

    public function setUp()
    {
        $this->em = new EventManager();
        $this->console = new ConsoleAdapter();
        $this->console->setCharset(new Ascii());
        $this->reporter = new VerboseConsole($this->console);
        $this->em->attachAggregate($this->reporter);
    }

    public function testConsoleSettingGetting()
    {
        $this->assertSame($this->console, $this->reporter->getConsole());

        $newConsole = new ConsoleAdapter();
        $this->reporter->setConsole($newConsole);
        $this->assertSame($newConsole, $this->reporter->getConsole());
    }

    public function testStartMessage()
    {
        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest()
        );
        $e->setParam('tests',$tests);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        $this->assertStringMatchesFormat('Starting diagnostics%A', ob_get_clean());
    }

    public function testSuccessProgress()
    {
        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest(),
            new AlwaysSuccessTest(),
        );
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $result = new Success();
        $e->setTarget($tests[0]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals('  OK   Always Successful Test' . PHP_EOL, ob_get_clean());
        ob_start();

        $result = new Success('this is a message');
        $e->setTarget($tests[1]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals('  OK   Always Successful Test: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testWarningProgress()
    {
        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest(),
            new AlwaysSuccessTest(),
        );
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $result = new Warning();
        $e->setTarget($tests[0]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(' WARN  Always Successful Test' . PHP_EOL, ob_get_clean());
        ob_start();

        $result = new Warning('this is a message');
        $e->setTarget($tests[1]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(' WARN  Always Successful Test: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testFailureProgress()
    {
        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest(),
            new AlwaysSuccessTest(),
        );
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $result = new Failure();
        $e->setTarget($tests[0]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(' FAIL  Always Successful Test' . PHP_EOL, ob_get_clean());
        ob_start();

        $result = new Failure('this is a message');
        $e->setTarget($tests[1]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(' FAIL  Always Successful Test: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testUnknownSymbols()
    {
        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest(),
            new AlwaysSuccessTest(),
        );
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $result = new Unknown();
        $e->setTarget($tests[0]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(' ????  Always Successful Test' . PHP_EOL, ob_get_clean());
        ob_start();

        $result = new Unknown('this is a message');
        $e->setTarget($tests[1]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(' ????  Always Successful Test: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testInfoOverflow()
    {
        $this->console->setTestWidth(40);

        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest(),
            new AlwaysSuccessTest(),
        );
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();


        $result = new Success(
            'foo foo foo foo foo foo foo foo foo foo foo foo foo foo foo foo foo'
        );
        $e->setTarget($tests[0]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(
            '  OK   Always Successful Test: foo foo' . PHP_EOL .
            '       foo foo foo foo foo foo foo foo' . PHP_EOL .
            '       foo foo foo foo foo foo foo'     . PHP_EOL
            , ob_get_clean()
        );
        ob_start();

        $result = new Failure(
            'foofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoo'
        );
        $e->setTarget($tests[1]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(
            ' FAIL  Always Successful Test:' . PHP_EOL .
            '       foofoofoofoofoofoofoofoofoofoofoo' . PHP_EOL .
            '       foofoofoofoofoofoofoofoofoofoofoo' . PHP_EOL .
            '       foo'                               . PHP_EOL
            , ob_get_clean()
        );
    }

    public function testDataDump()
    {
        $this->console->setTestWidth(40);
        $this->reporter->getDisplayData();
        $this->reporter->setDisplayData(true);

        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest(),
            new AlwaysSuccessTest(),
        );
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();


        $result = new Success('foo', array('1',2,3));
        $e->setTarget($tests[0]);
        $e->setLastResult($result);
        $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        $this->assertEquals(
            '  OK   Always Successful Test: foo'       . PHP_EOL .
            '       ---------------------------------' . PHP_EOL .
            '       array ('                           . PHP_EOL .
            '         0 => \'1\','                     . PHP_EOL .
            '         1 => 2,'                         . PHP_EOL .
            '         2 => 3,'                         . PHP_EOL .
            '       )'                                 . PHP_EOL .
            '       ---------------------------------' . PHP_EOL
            , ob_get_clean()
        );
        ob_start();
    }

    public function testSummaryAllSuccessful()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for($x = 0; $x < 20; $x++){
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringStartsWith('OK (20 diagnostic tests)', trim(ob_get_clean()));
    }

    public function testSummaryWithWarnings()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Warning();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringStartsWith('5 warnings, 15 successful tests', trim(ob_get_clean()));
    }

    public function testSummaryWithFailures()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Warning();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Failure();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringStartsWith('5 failures, 5 warnings, 15 successful tests', trim(ob_get_clean()));
    }

    public function testSummaryWithUnknowns()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Warning();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Unknown();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringMatchesFormat('%A5 unknown test results%A', trim(ob_get_clean()));
    }

    public function testSummaryWithUnknownsAndFailures()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Failure();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Unknown();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $result = trim(ob_get_clean());
        $this->assertStringMatchesFormat('%A5 failures%A', $result);
        $this->assertStringMatchesFormat('%A5 unknown test results%A', $result);
    }

    public function testStoppedNotice()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_STOP, $e);

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringMatchesFormat('%ADiagnostics aborted%A', trim(ob_get_clean()));
    }


}
