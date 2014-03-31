<?php
namespace ZFToolTest\Diagnostics\Reporter;

use ZFTool\Diagnostics\Reporter\VerboseConsole;
use ZendDiagnostics\Result\Collection;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;
use ZFToolTest\Diagnostics\TestAsset\UnknownResult as Unknown;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessCheck;
use ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter;
use Zend\Console\Charset\Ascii;
use ArrayObject;

require_once __DIR__.'/../TestAsset/AlwaysSuccessCheck.php';
require_once __DIR__.'/../TestAsset/ConsoleAdapter.php';
require_once __DIR__.'/../TestAsset/UnknownResult.php';

class VerboseConsoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConsoleAdapter
     */
    protected $console;

    /**
     * @var VerboseConsole
     */
    protected $reporter;

    public function setUp()
    {
        $this->console = new ConsoleAdapter();
        $this->console->setCharset(new Ascii());
        $this->reporter = new VerboseConsole($this->console);
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
        $checks = new ArrayObject(array(
            new AlwaysSuccessCheck()
        ));

        ob_start();
        $this->reporter->onStart($checks, array());
        $this->assertStringMatchesFormat('Starting diagnostics%A', ob_get_clean());
    }

    public function testSuccessProgress()
    {
        $checks = new ArrayObject(array(
            new AlwaysSuccessCheck(),
            new AlwaysSuccessCheck(),
        ));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $result = new Success();
        $this->reporter->onAfterRun($checks[0], $result);
        $this->assertEquals('  OK   Always Successful Check' . PHP_EOL, ob_get_clean());

        ob_start();
        $result = new Success('this is a message');
        $this->reporter->onAfterRun($checks[1], $result);
        $this->assertEquals('  OK   Always Successful Check: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testWarningProgress()
    {
        $checks = new ArrayObject(array(
            new AlwaysSuccessCheck(),
            new AlwaysSuccessCheck(),
        ));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $result = new Warning();
        $this->reporter->onAfterRun($checks[0], $result);
        $this->assertEquals(' WARN  Always Successful Check' . PHP_EOL, ob_get_clean());

        ob_start();
        $result = new Warning('this is a message');
        $this->reporter->onAfterRun($checks[1], $result);
        $this->assertEquals(' WARN  Always Successful Check: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testFailureProgress()
    {
        $checks = new ArrayObject(array(
            new AlwaysSuccessCheck(),
            new AlwaysSuccessCheck(),
        ));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $result = new Failure();
        $this->reporter->onAfterRun($checks[0], $result);
        $this->assertEquals(' FAIL  Always Successful Check' . PHP_EOL, ob_get_clean());

        ob_start();
        $result = new Failure('this is a message');
        $this->reporter->onAfterRun($checks[1], $result);
        $this->assertEquals(' FAIL  Always Successful Check: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testUnknownSymbols()
    {
        $checks = new ArrayObject(array(
            new AlwaysSuccessCheck(),
            new AlwaysSuccessCheck(),
        ));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $result = new Unknown();
        $this->reporter->onAfterRun($checks[0], $result);
        $this->assertEquals(' ????  Always Successful Check' . PHP_EOL, ob_get_clean());

        ob_start();
        $result = new Unknown('this is a message');
        $this->reporter->onAfterRun($checks[1], $result);
        $this->assertEquals(' ????  Always Successful Check: this is a message' . PHP_EOL, ob_get_clean());
    }

    public function testInfoOverflow()
    {
        $this->console->setTestWidth(40);

        $checks = new ArrayObject(array(
            new AlwaysSuccessCheck(),
            new AlwaysSuccessCheck(),
        ));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $result = new Success(
            'foo foo foo foo foo foo foo foo foo foo foo foo foo foo foo foo foo'
        );

        $this->reporter->onAfterRun($checks[0], $result);
        $this->assertEquals(
            '  OK   Always Successful Check: foo foo' . PHP_EOL .
            '       foo foo foo foo foo foo foo foo' . PHP_EOL .
            '       foo foo foo foo foo foo foo'     . PHP_EOL
            , ob_get_clean()
        );
        ob_start();

        $result = new Failure(
            'foofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoo'
        );

        $this->reporter->onAfterRun($checks[1], $result);
        $this->assertEquals(
            ' FAIL  Always Successful Check:'           . PHP_EOL .
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

        $checks = new ArrayObject(array(
            new AlwaysSuccessCheck(),
            new AlwaysSuccessCheck(),
        ));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $result = new Success('foo', array('1',2,3));
        $this->reporter->onAfterRun($checks[0], $result);
        $this->assertEquals(
            '  OK   Always Successful Check: foo'       . PHP_EOL .
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

        $checks = new ArrayObject(array());
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 20; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $this->assertStringStartsWith('OK (20 diagnostic checks)', trim(ob_get_clean()));
    }

    public function testSummaryWithWarnings()
    {
        $checks = new ArrayObject(array());
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Warning();
        }

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $this->assertStringStartsWith('5 warnings, 15 successful checks', trim(ob_get_clean()));
    }

    public function testSummaryWithFailures()
    {
        $checks = new ArrayObject(array());
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Warning();
        }

        for ($x = 0; $x < 5; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Failure();
        }

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $this->assertStringStartsWith('5 failures, 5 warnings, 15 successful checks', trim(ob_get_clean()));
    }

    public function testSummaryWithUnknowns()
    {
        $checks = new ArrayObject(array());
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Warning();
        }

        for ($x = 0; $x < 5; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Unknown();
        }

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $this->assertStringMatchesFormat('%A5 unknown check results%A', trim(ob_get_clean()));
    }

    public function testSummaryWithUnknownsAndFailures()
    {
        $checks = new ArrayObject(array());
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Failure();
        }

        for ($x = 0; $x < 5; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Unknown();
        }

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $result = trim(ob_get_clean());
        $this->assertStringMatchesFormat('%A5 failures%A', $result);
        $this->assertStringMatchesFormat('%A5 unknown check results%A', $result);
    }

    public function testStoppedNotice()
    {
        $checks = new ArrayObject(array());
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onStop($results);
        $this->reporter->onFinish($results);
        $this->assertStringMatchesFormat('%ADiagnostics aborted%A', trim(ob_get_clean()));
    }
}
