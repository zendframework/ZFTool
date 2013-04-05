<?php
namespace ZFToolTest\Diagnostics\Test;

use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Test\Callback;
use ZFTool\Diagnostics\Test\ClassExists;
use ZFTool\Diagnostics\Test\CpuPerformance;
use ZFTool\Diagnostics\Test\ExtensionLoaded;
use ZFTool\Diagnostics\Test\PhpVersion;
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
        $test = new CpuPerformance(0);          // minimum threshold
        $result = $test->run();
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $result);

        $test = new CpuPerformance(999999999);  // improbable to archive
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
        $test = new PhpVersion(PHP_VERSION);      // default operator
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new PhpVersion(PHP_VERSION, '='); // explicit equal
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $test = new PhpVersion(PHP_VERSION, '<'); // explicit less than
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());
    }

    public function testPhpVersionArray()
    {
        $test = new PhpVersion(array(PHP_VERSION));      // default operator
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
        $test = new Callback(function() use (&$called, $expectedResult) {
            $called = true;
            return $expectedResult;
        });
        $result= $test->run();
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
        foreach(array_rand($allExtensions, 3) as $key) {
            $extensions[] = $allExtensions[$key];
        }

        $test = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $test->run());

        $extensions[] = 'improbableExtName9999999999999999999999';

        $test = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $test->run());
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


}