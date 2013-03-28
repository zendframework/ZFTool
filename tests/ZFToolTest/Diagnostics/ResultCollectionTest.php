<?php
namespace ZFToolTest\Diagnostics;

use ZFTool\Diagnostics\Result\Collection;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest;

require_once __DIR__.'/TestAsset/AlwaysSuccessTest.php';

class ResultCollectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Collection
     */
    protected $collection;

    public function setUp()
    {
        $this->collection = new Collection();
    }

    public function tearDown(){}

    public static function invalidKeysProvider(){
        return array(
            array(0),
            array(1),
            array('foo'),
            array(new \stdClass),
            array(new \ArrayObject),
            array(new Success()),
        );
    }

    public static function invalidValuesProvider(){
        return array(
            array(0),
            array(1),
            array('foo'),
            array(new \stdClass),
            array(new \ArrayObject),
            array(new AlwaysSuccessTest()),
        );
    }

    public function testClassCapabilities()
    {
        $this->assertInstanceOf('ArrayObject', $this->collection);
        $this->assertInstanceOf('Traversable', $this->collection);
    }

    public function testBasicGettingAndSetting()
    {
        $test = new AlwaysSuccessTest();
        $result = new Success();

        $this->collection[$test] = $result;
        $this->assertSame($result, $this->collection[$test]);

        unset($this->collection[$test]);
        $this->assertFalse($this->collection->offsetExists($test));
    }

    /**
     * @dataProvider invalidKeysProvider
     */
    public function testInvalidKeySet($key)
    {
        $result = new Success();

        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->collection[$key] = $result;
    }

    /**
     * @dataProvider invalidKeysProvider
     */
    public function testInvalidKeyGet($key)
    {
        $result = new Success();

        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->collection[$key];
    }

    /**
     * @dataProvider invalidKeysProvider
     */
    public function testInvalidKeyUnset($key)
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->collection->offsetUnset($key);
    }

    /**
     * @dataProvider invalidKeysProvider
     */
    public function testInvalidKeyExists($key)
    {
        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->collection->offsetExists($key);
    }

    /**
     * @dataProvider invalidValuesProvider
     */
    public function testInvalidValuesSet($value)
    {
        $key = new AlwaysSuccessTest();

        $this->setExpectedException('ZFTool\Diagnostics\Exception\InvalidArgumentException');
        $this->collection[$key] = $value;
    }

    public function testCounters()
    {
        $this->assertEquals(0, $this->collection->getSuccessCount());
        $this->assertEquals(0, $this->collection->getWarningCount());
        $this->assertEquals(0, $this->collection->getFailureCount());

        $success1 = new Success();
        $test1 = new AlwaysSuccessTest();
        $this->collection[$test1] = $success1;
        $this->assertEquals(1, $this->collection->getSuccessCount());
        $this->assertEquals(0, $this->collection->getWarningCount());
        $this->assertEquals(0, $this->collection->getFailureCount());

        $success2 = new Success();
        $test2 = new AlwaysSuccessTest();
        $this->collection[$test2] = $success2;
        $this->assertEquals(2, $this->collection->getSuccessCount());
        $this->assertEquals(0, $this->collection->getWarningCount());
        $this->assertEquals(0, $this->collection->getFailureCount());

        $failure1 = new Failure();
        $test3 = new AlwaysSuccessTest();
        $this->collection[$test3] = $failure1;
        $this->assertEquals(2, $this->collection->getSuccessCount());
        $this->assertEquals(0, $this->collection->getWarningCount());
        $this->assertEquals(1, $this->collection->getFailureCount());

        $warning1 = new Warning();
        $test4 = new AlwaysSuccessTest();
        $this->collection[$test4] = $warning1;
        $this->assertEquals(2, $this->collection->getSuccessCount());
        $this->assertEquals(1, $this->collection->getWarningCount());
        $this->assertEquals(1, $this->collection->getFailureCount());

        $failure2 = new Failure();
        $this->collection[$test2] = $failure2;
        $this->assertEquals(1, $this->collection->getSuccessCount());
        $this->assertEquals(1, $this->collection->getWarningCount());
        $this->assertEquals(2, $this->collection->getFailureCount());

        unset($this->collection[$test4]);
        $this->assertEquals(1, $this->collection->getSuccessCount());
        $this->assertEquals(0, $this->collection->getWarningCount());
        $this->assertEquals(2, $this->collection->getFailureCount());

    }

    public function testIteration()
    {
        $tests = $results = array();
        $test = $result = null;

        for($x = 0; $x < 10; $x++){
            $tests[] = $test = new AlwaysSuccessTest();
            $results[] = $result = new Success();
            $this->collection[$test] = $result;
        }

        $x = 0;
        foreach($this->collection as $test => $result){
            $this->assertSame($tests[$x], $test);
            $this->assertSame($results[$x], $result);
            $x++;
        }
    }

}
