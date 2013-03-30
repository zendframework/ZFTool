<?php
namespace ZFToolTest\Diagnostics\Test;

use ZFTool\Diagnostics\Test\ClassExists;
use ZFTool\Diagnostics\Test\CpuPerformance;
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

}