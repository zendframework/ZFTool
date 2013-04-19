<?php
namespace ZFToolTest\Diagnostics\Test;

use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\Callback;
use ZFTool\Diagnostics\Test\ClassExists;
use ZFTool\Diagnostics\Test\CpuPerformance;
use ZFTool\Diagnostics\Test\DirReadable;
use ZFTool\Diagnostics\Test\DirWritable;
use ZFTool\Diagnostics\Test\ExtensionLoaded;
use ZFTool\Diagnostics\Test\PhpVersion;
use ZFTool\Diagnostics\Test\StreamWrapperExists;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest;

class BasicTestsTest extends \PHPUnit_Framework_TestCase
{
    public function testLabels()
    {
        $label = md5(rand());
        $test = new AlwaysSuccessTest();
        $test->setLabel($label);
        $this->assertEquals($label, $test->getLabel());
    }

    public function testCpuPerformance()
    {
        $test = new CpuPerformance(0); // minimum threshold
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $result);

        $test = new CpuPerformance(999999999); // improbable to archive
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
    }

    public function testClassExists()
    {
        $test = new ClassExists(__CLASS__);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new ClassExists('improbableClassNameInGlobalNamespace999999999999999999');
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());

        $test = new ClassExists(array(
            __CLASS__,
            'ZFTool\Diagnostics\Result\Success',
            'ZFTool\Diagnostics\Result\Failure',
            'ZFTool\Diagnostics\Result\Warning',
        ));
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new ClassExists(array(
            __CLASS__,
            'ZFTool\Diagnostics\Result\Success',
            'improbableClassNameInGlobalNamespace999999999999999999',
            'ZFTool\Diagnostics\Result\Failure',
            'ZFTool\Diagnostics\Result\Warning',
        ));
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());
    }

    public function testPhpVersion()
    {
        $test = new PhpVersion(PHP_VERSION); // default operator
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new PhpVersion(PHP_VERSION, '='); // explicit equal
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new PhpVersion(PHP_VERSION, '<'); // explicit less than
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());
    }

    public function testPhpVersionArray()
    {
        $test = new PhpVersion(array(PHP_VERSION)); // default operator
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new PhpVersion(array(
            '1.0.0',
            '1.1.0',
            '1.1.1',
        ), '<'); // explicit less than
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());

        $test = new PhpVersion(new \ArrayObject(array(
            '40.0.0',
            '41.0.0',
            '42.0.0',
        )), '<'); // explicit less than
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new PhpVersion(new \ArrayObject(array(
            '41.0.0',
            PHP_VERSION,
        )), '!='); // explicit less than
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());

    }

    public function testCallback()
    {
        $called = false;
        $expectedResult = new Success();
        $test = new Callback(function () use (&$called, $expectedResult) {
            $called = true;

            return $expectedResult;
        });
        $result = $test->run();
        $this->assertTrue($called);
        $this->assertSame($expectedResult, $result);
    }

    public function testExtensionLoaded()
    {
        $allExtensions = get_loaded_extensions();
        $ext1 = $allExtensions[array_rand($allExtensions)];

        $test = new ExtensionLoaded($ext1);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new ExtensionLoaded('improbableExtName999999999999999999');
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());

        $extensions = array();
        foreach (array_rand($allExtensions, 3) as $key) {
            $extensions[] = $allExtensions[$key];
        }

        $test = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $extensions[] = 'improbableExtName9999999999999999999999';

        $test = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());

        $extensions = array(
            'improbableExtName9999999999999999999999',
            'improbableExtName0000000000000000000000',
        );

        $test = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());
    }

    public function testStreamWrapperExists()
    {
        $allWrappers = stream_get_wrappers();
        $wrapper = $allWrappers[array_rand($allWrappers)];

        $test = new StreamWrapperExists($wrapper);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new StreamWrapperExists('improbableName999999999999999999');
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());

        $wrappers = array();
        foreach (array_rand($allWrappers, 3) as $key) {
            $wrappers[] = $allWrappers[$key];
        }

        $test = new StreamWrapperExists($wrappers);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $wrappers[] = 'improbableName9999999999999999999999';

        $test = new StreamWrapperExists($wrappers);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());

        $wrappers = array(
            'improbableName9999999999999999999999',
            'improbableName0000000000000000000000',
        );

        $test = new StreamWrapperExists($wrappers);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());
    }

    public function testDirReadable()
    {
        $test = new DirReadable(__DIR__);
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $result);

        $test = new DirReadable(array(
            __DIR__,
            __DIR__.'/../'
        ));
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $result);

        $test = new DirReadable(__FILE__);
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);

        $test = new DirReadable(__DIR__ . '/improbabledir99999999999999999999999999999999999999');
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);

        $tmpDir = sys_get_temp_dir();
        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');

            return;
        }

        // generate a random dir name
        while (($dir = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir)) {
        }

        // create the temporary writable directory
        if (!mkdir($dir) || !chmod($dir, 0000)) {
            $this->markTestSkipped('Cannot create unreadable temporary directory to perform the test... ');
            return;
        }

        $test = new DirReadable($dir);
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);

        chmod($dir, 0777);
        rmdir($dir);

    }

    public function testDirWritable()
    {
        $tmpDir = sys_get_temp_dir();
        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');

            return;
        }

        // generate a random dir name
        while (($dir = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir)) {
        }

        // create the temporary writable directory
        if (!mkdir($dir)) {
            $this->markTestSkipped('Cannot create writable temporary directory to perform the test... ');
            return;
        }

        $test = new DirWritable($dir);
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $result);

        // Disallow writing to the directory to anyone
        chmod($dir, 0000);

        $test = new DirWritable(array($dir));
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
        chmod($dir, 0777);
        rmdir($dir);

        $test = new DirWritable(__DIR__ . '/improbabledir99999999999999999999999999999999999999');
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $result);
    }

    public function testPhpVersionInvalidVersion()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new PhpVersion(new \stdClass());
    }

    public function testPhpVersionInvalidVersion2()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new PhpVersion(fopen('php://memory', 'r'));
    }

    public function testPhpVersionInvalidOperator()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new PhpVersion('1.0.0', array());
    }

    public function testPhpVersionInvalidOperator2()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new PhpVersion('1.0.0', 'like');
    }

    public function testClassExistsInvalidArgument()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new ClassExists(new \stdClass);
    }

    public function testClassExistsInvalidArgument2()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new ClassExists(15);
    }

    public function testExtensionLoadedInvalidArgument()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new ExtensionLoaded(new \stdClass);
    }

    public function testExtensionLoadedInvalidArgument2()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new ExtensionLoaded(15);
    }

    public function testDirReadableInvalidArgument()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new DirReadable(new \stdClass);
    }

    public function testDirReadableInvalidArgument2()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new DirReadable(15);
    }

    public function testDirWritableInvalidArgument()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new DirWritable(new \stdClass);
    }

    public function testDirWritableInvalidArgument2()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new DirWritable(15);
    }

    public function testStreamWrapperInvalidArgument()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new StreamWrapperExists(new \stdClass);
    }

    public function testStreamWrapperInvalidInvalidArgument2()
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        new StreamWrapperExists(15);
    }


}
