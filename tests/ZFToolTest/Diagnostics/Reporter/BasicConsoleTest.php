<?php
namespace ZFToolTest\Diagnostics\Reporter;

use ArrayObject;
use Zend\Console\Charset\Ascii;
use ZendDiagnostics\Result\Collection;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;
use ZFTool\Diagnostics\Reporter\BasicConsole;
use ZFToolTest\Diagnostics\TestAsset\UnknownResult as Unknown;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessCheck;
use ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter;
use ZFToolTest\Diagnostics\TestAssets\DummyReporter;

require_once __DIR__.'/../TestAsset/AlwaysSuccessCheck.php';
require_once __DIR__.'/../TestAsset/ConsoleAdapter.php';
require_once __DIR__.'/../TestAsset/DummyReporter.php';
require_once __DIR__.'/../TestAsset/UnknownResult.php';

class BasicConsoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter
     */
    protected $console;

    /**
     * @var \ZFTool\Diagnostics\Reporter\BasicConsole
     */
    protected $reporter;

    /**
     * @var \Zend\EventManager\EventManager;
     */
    protected $em;

    public function setUp()
    {
        $this->console = new ConsoleAdapter();
        $this->console->setCharset(new Ascii());
        $this->console->setTestUtf8(true);
        $this->assertEquals(true, $this->console->isUtf8());
        $this->reporter = new BasicConsole($this->console);
    }

    public function testDummyReporter()
    {
        $reporter = new DummyReporter();
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
        $this->assertStringMatchesFormat('Starting%A', ob_get_clean());
    }

    public function testProgressDots()
    {
        $checks = new ArrayObject(array_fill(0,5, new AlwaysSuccessCheck()));
        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        foreach ($checks as $check) {
            $result = new Success();
            $this->reporter->onAfterRun($check, $result);
        }

        $this->assertEquals('.....', ob_get_clean());
    }

    public function testWarningSymbols()
    {
        $checks = new ArrayObject(array_fill(0,5, new AlwaysSuccessCheck()));
        ob_start();
        $this->reporter->onStart($checks, array());
        ob_get_clean();

        ob_start();
        foreach ($checks as $check) {
            $result = new Warning();
            $this->reporter->onAfterRun($check, $result);
        }

        $this->assertEquals('!!!!!', ob_get_clean());
    }

    public function testFailureSymbols()
    {

        $checks = new ArrayObject(array_fill(0,5, new AlwaysSuccessCheck()));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_get_clean();

        ob_start();
        foreach ($checks as $check) {
            $result = new Failure();
            $this->reporter->onAfterRun($check, $result);
        }

        $this->assertEquals('FFFFF', ob_get_clean());
    }

    public function testUnknownSymbols()
    {

        $checks = new ArrayObject(array_fill(0,5, new AlwaysSuccessCheck()));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_get_clean();

        ob_start();
        foreach ($checks as $check) {
            $result = new Unknown();

            $this->reporter->onAfterRun($check, $result);
        }

        $this->assertEquals('?????', ob_get_clean());
    }

    public function testProgressDotsNoGutter()
    {

        $this->console->setTestWidth(40);
        $checks = new ArrayObject(array_fill(0,40, new AlwaysSuccessCheck()));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_get_clean();

        ob_start();
        foreach ($checks as $check) {
            $result = new Success();
            $this->reporter->onAfterRun($check, $result);
        }

        $this->assertEquals(str_repeat('.', 40), ob_get_clean());
    }

    public function testProgressOverflow()
    {
        $this->console->setTestWidth(40);
        $checks = new ArrayObject(array_fill(0,80, new AlwaysSuccessCheck()));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_get_clean();

        ob_start();
        foreach ($checks as $check) {
            $result = new Success();
            $this->reporter->onAfterRun($check, $result);
        }

        $expected  = '......................... 25 / 80 ( 31%)';
        $expected .= '......................... 50 / 80 ( 63%)';
        $expected .= '......................... 75 / 80 ( 94%)';
        $expected .= '.....';

        $this->assertEquals($expected, ob_get_clean());
    }

    public function testProgressOverflowMatch()
    {

        $this->console->setTestWidth(40);
        $checks = new ArrayObject(array_fill(0,75, new AlwaysSuccessCheck()));

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_get_clean();

        ob_start();
        foreach ($checks as $check) {
            $result = new Success();
            $this->reporter->onAfterRun($check, $result);
        }

        $expected  = '......................... 25 / 75 ( 33%)';
        $expected .= '......................... 50 / 75 ( 67%)';
        $expected .= '......................... 75 / 75 (100%)';

        $this->assertEquals($expected, ob_get_clean());
    }

    public function testSummaryAllSuccessful()
    {
        $checks = new ArrayObject();
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
        $checks = new ArrayObject();
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
        $checks = new ArrayObject();
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
        $checks = new ArrayObject();
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

    public function testWarnings()
    {
        $checks = new ArrayObject();
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        $checks[] = $check = new AlwaysSuccessCheck();
        $results[$check] = new Warning('foo');

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $this->assertStringMatchesFormat(
            '%AWarning: Always Successful Check%wfoo',
            trim(ob_get_clean())
        );
    }

    public function testFailures()
    {
        $checks = new ArrayObject();
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        $checks[] = $check = new AlwaysSuccessCheck();
        $results[$check] = new Failure('bar');

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $this->assertStringMatchesFormat(
            '%AFailure: Always Successful Check%wbar',
            trim(ob_get_clean())
        );
    }

    public function testUnknowns()
    {
        $checks = new ArrayObject();
        $check = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $checks[] = $check = new AlwaysSuccessCheck();
            $results[$check] = new Success();
        }

        $checks[] = $check = new AlwaysSuccessCheck();
        $results[$check] = new Unknown('baz');

        ob_start();
        $this->reporter->onStart($checks, array());
        ob_clean();

        $this->reporter->onFinish($results);
        $this->assertStringMatchesFormat(
            '%AUnknown result ZFToolTest\Diagnostics\TestAsset\UnknownResult: Always Successful Check%wbaz%A',
            trim(ob_get_clean())
        );
    }

    public function testStoppedNotice()
    {
        $checks = new ArrayObject();
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
