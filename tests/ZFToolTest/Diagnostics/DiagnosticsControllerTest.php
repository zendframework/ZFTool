<?php
namespace ZFToolTest\Diagnostics\Test;

use ZFTool\Controller\DiagnosticsController;
use ZFTool\Diagnostics\Exception\RuntimeException;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use ZFTool\Diagnostics\Test\Callback;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest;
use ZFToolTest\Diagnostics\TestAsset\ReturnThisTest;
use ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter;
use ZFToolTest\DummyModule;
use ZFToolTest\TestAsset\InjectableModuleManager;
use Zend\Console\Request as ConsoleRequest;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayObject;
use Zend\Stdlib\ArrayUtils;

require_once __DIR__.'/TestAsset/ConsoleAdapter.php';
require_once __DIR__.'/TestAsset/InjectableModuleManager.php';
require_once __DIR__.'/TestAsset/ReturnThisTest.php';
require_once __DIR__.'/TestAsset/AlwaysSuccessTest.php';
require_once __DIR__.'/TestAsset/DummyModule.php';

class DiagnosticsControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $sm;

    /**
     * @var InjectableModuleManager
     */
    protected $mm;

    /**
     * @var DiagnosticsController
     */
    protected $controller;

    /**
     * @var RouteMatch
     */
    protected $routeMatch;

    /**
     * @var ArrayObject
     */
    protected $config;

    protected static $staticTestMethodCalled = false;

    public function setup()
    {
        $this->config = new ArrayObject(array(
            'diagnostics' => array()
        ));

        $this->sm = new ServiceManager();
        $this->sm->setService('console', new ConsoleAdapter());
        $this->sm->setService('config', $this->config);
        $this->sm->setAlias('configuration','config');

        $this->mm = new InjectableModuleManager();
        $this->sm->setService('modulemanager', $this->mm);

        $event = new MvcEvent();
        $this->routeMatch = new RouteMatch(array(
            'controller' => 'ZFTools\Controller\Diagnostics',
            'action'     => 'run'
        ));
        $event->setRouteMatch($this->routeMatch);
        $this->controller = new DiagnosticsController();
        $this->controller->setServiceLocator($this->sm);
        $this->controller->setEvent($event);
    }

    public function invalidDefinitionsProvider()
    {
        $res = fopen('php://memory', 'r');
        fclose($res);

        return array(
            'an empty array' => array(
                 array(),
                'Cannot use an empty array%a'
            ),
            'an invalid test instance' => array(
                new \stdClass(),
                'Cannot use object of class "stdClass"%a'
            ),
            'an unknown definition type' => array(
                $res,
                'Cannot understand diagnostic test definition %a'
            ),
            'an invalid class name' => array(
                'stdClass',
                'The test object of class stdClass does not implement ZFTool\Diagnostics\Test\TestInterface'
            ),
            'an unknown test identifier' => array(
                'some\unknown\class\or\service\identifier',
                'Cannot find test class or service with the name of "some\unknown\class\or\service\identifier"%a'
            )
        );
    }

    public function testEmptyResult()
    {
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));
        $this->assertEquals(0, $result->getVariable('results')->count());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => new Test()
     *      )
     *  )
     */
    public function testConfigBasedTestInstance()
    {
        $expectedResult = new Success('bar');
        $test = new ReturnThisTest($expectedResult);
        $this->config['diagnostics']['group']['foo'] = $test;
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $this->assertTrue($results->offsetExists($test));
        $this->assertSame($expectedResult, $results[$test]);
        $this->assertSame('group: foo', $test->getLabel());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => 'My\Namespace\ClassName'
     *      )
     *  )
     */
    public function testConfigBasedTestClassName()
    {
        $this->config['diagnostics']['group']['foo'] = 'ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest';
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $test = array_pop($tests);

        $this->assertInstanceOf('ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest', $test);
        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => array('My\Namespace\ClassName', 'methodName')
     *      )
     *  )
     */
    public function testConfigBasedStaticMethod()
    {
        static::$staticTestMethodCalled = false;
        $this->config['diagnostics']['group']['foo'] = array(__CLASS__, 'staticTestMethod');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $test = array_pop($tests);

        $this->assertInstanceOf('ZFTool\Diagnostics\Test\Callback', $test);
        $this->assertTrue(static::$staticTestMethodCalled);
        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
        $this->assertEquals('bar', $results[$test]->getMessage());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => array(
     *              array('My\Namespace\ClassName', 'methodName'),
     *              'param1',
     *              'param2',
     *          )
     *      )
     *  )
     */
    public function testConfigBasedStaticMethodWithParams()
    {
        static::$staticTestMethodCalled = false;
        $expectedData = mt_rand(1,PHP_INT_MAX);
        $expectedMessage = mt_rand(1,PHP_INT_MAX);
        $this->config['diagnostics']['group']['foo'] = array(
            array(__CLASS__, 'staticTestMethod'),
            $expectedMessage,
            $expectedData
        );
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $test = array_pop($tests);

        $this->assertInstanceOf('ZFTool\Diagnostics\Test\Callback', $test);
        $this->assertTrue(static::$staticTestMethodCalled);
        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
        $this->assertEquals($expectedMessage, $results[$test]->getMessage());
        $this->assertEquals($expectedData, $results[$test]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => 'someFunctionName'
     *      )
     *  )
     */
    public function testConfigBasedFunction()
    {
        $this->config['diagnostics']['group']['foo'] = __NAMESPACE__ . '\testOutlineFunction';
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $test = array_pop($tests);

        $this->assertInstanceOf('ZFTool\Diagnostics\Test\Callback', $test);
        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
        $this->assertEquals('bar', $results[$test]->getMessage());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => array('someFunctionName', 'param1', 'param2')
     *      )
     *  )
     */
    public function testConfigBasedFunctionWithParams()
    {
        $expectedData = mt_rand(1,PHP_INT_MAX);
        $expectedMessage = mt_rand(1,PHP_INT_MAX);
        $this->config['diagnostics']['group']['foo'] = array(
            __NAMESPACE__ . '\testOutlineFunction',
            $expectedMessage,
            $expectedData
        );
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $test = array_pop($tests);

        $this->assertInstanceOf('ZFTool\Diagnostics\Test\Callback', $test);
        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
        $this->assertEquals($expectedMessage, $results[$test]->getMessage());
        $this->assertEquals($expectedData, $results[$test]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => array('ClassExists', 'params')
     *      )
     *  )
     */
    public function testConfigBasedBuiltinTest()
    {
        $this->config['diagnostics']['group']['foo'] = array('ClassExists', __CLASS__);
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $test = array_pop($tests);

        $this->assertInstanceOf('ZFTool\Diagnostics\Test\ClassExists', $test);
        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => 'Some\ServiceManager\Identifier'
     *      )
     *  ),
     *  'service_manager' => array(
     *      'invokables' => array(
     *          'Some\ServiceManager\Identifier' => 'Some\Test\Class'
     *      )
     *  )
     */
    public function testConfigBasedServiceName()
    {
        $expectedData = mt_rand(1,PHP_INT_MAX);
        $expectedMessage = mt_rand(1,PHP_INT_MAX);
        $test = new Callback(function () use ($expectedMessage, $expectedData) {
            return new Success($expectedMessage, $expectedData);
        });
        $this->sm->setService('ZFToolTest\TestService', $test);

        $this->config['diagnostics']['group']['foo'] = 'ZFToolTest\TestService';

        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($test, array_pop($tests));

        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
        $this->assertEquals($expectedMessage, $results[$test]->getMessage());
        $this->assertEquals($expectedData, $results[$test]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'test label' => 'PhpVersion'
     *      )
     *  )
     */
    public function testBuiltInBeforeCallable()
    {
        $this->config['diagnostics']['group']['foo'] = array('PhpVersion', '1.0.0');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $test = array_pop($tests);

        $this->assertInstanceOf('ZFTool\Diagnostics\Test\PhpVersion', $test);
    }


    public function testModuleProvidedDefinitions()
    {
        $module = new DummyModule($this->sm);
        $this->mm->injectModule('dummymodule',$module);
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(5, $results->count());

        $expected = array(
            array('dummymodule: test1', 'ZFTool\Diagnostics\Result\Success', 'test1 success'),
            array('dummymodule: test2', 'ZFTool\Diagnostics\Result\Success', ''),
            array('dummymodule: test3', 'ZFTool\Diagnostics\Result\Failure', ''),
            array('dummymodule: test4', 'ZFTool\Diagnostics\Result\Failure', 'static test message'),
            array('dummymodule: test5', 'ZFTool\Diagnostics\Result\Failure', 'someOtherMessage'),
        );

        $x = 0;
        foreach($results as $test){
            $result = $results[$test];
            list($label, $class, $message) = $expected[$x++];
            error_reporting(E_ERROR);
            $this->assertInstanceOf('ZFTool\Diagnostics\Test\TestInterface', $test);
            $this->assertEquals($label,   $test->getLabel());
            $this->assertEquals($message, $result->getMessage());
            $this->assertInstanceOf($class, $result);
        }
    }

    public function testTriggerAWarning()
    {
        $test = new Callback(function () {
            1/0; // < throw a warning
        });

        $this->config['diagnostics']['group']['foo'] = $test;

        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($test, array_pop($tests));

        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
    }

    public function testThrowingAnException()
    {
        $e = new \Exception();
        $test = new Callback(function () use (&$e) {
            throw $e;
        });

        $this->config['diagnostics']['group']['foo'] = $test;

        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($test, array_pop($tests));

        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
        $this->assertSame($e, $results[$test]->getData());
    }

    public function testInvalidResult()
    {
        $someObj = new \stdClass;
        $test = new ReturnThisTest($someObj);
        $this->config['diagnostics']['group']['foo'] = $test;

        $dispatchResult = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $dispatchResult);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $dispatchResult->getVariable('results'));
        $results = $dispatchResult->getVariable('results');
        $this->assertEquals(1, $results->count());
        $test = array_pop(ArrayUtils::iteratorToArray(($results)));
        $this->assertSame('group: foo', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
        $this->assertSame($someObj, $results[$test]->getData());

        $someResource = fopen('php://memory','r');
        fclose($someResource);
        $test = new ReturnThisTest($someResource);
        $this->config['diagnostics']['group']['foo'] = $test;
        $dispatchResult = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $dispatchResult);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $dispatchResult->getVariable('results'));
        $results = $dispatchResult->getVariable('results');
        $test = array_pop(ArrayUtils::iteratorToArray(($results)));
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
        $this->assertSame($someResource, $results[$test]->getData());

        $test = new ReturnThisTest(123);
        $this->config['diagnostics']['group']['foo'] = $test;
        $dispatchResult = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $dispatchResult);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $dispatchResult->getVariable('results'));
        $results = $dispatchResult->getVariable('results');
        $test = array_pop(ArrayUtils::iteratorToArray(($results)));
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Warning', $results[$test]);
        $this->assertEquals(123, $results[$test]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *           'Some\Test',
     *           'Some\Other\Test',
     *           'test3' => 'Another\One'
     *      )
     *  ),
     */
    public function testIgnoreNumericLabel()
    {
        $this->config['diagnostics']['group'][] = array('ClassExists',__CLASS__);
        $this->config['diagnostics']['group'][] = array('ClassExists',__CLASS__);
        $this->config['diagnostics']['group']['test3'] = array('ClassExists',__CLASS__);
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(3, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));

        $test = array_shift($tests);
        $this->assertInstanceOf('ZFTool\Diagnostics\Test\ClassExists', $test);
        $this->assertNull($test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);

        $test = array_shift($tests);
        $this->assertInstanceOf('ZFTool\Diagnostics\Test\ClassExists', $test);
        $this->assertNull($test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);

        $test = array_shift($tests);
        $this->assertInstanceOf('ZFTool\Diagnostics\Test\ClassExists', $test);
        $this->assertSame('group: test3', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
    }

    /**
     * @dataProvider invalidDefinitionsProvider
     */
    public function testInvalidDefinitions($definition, $exceptionMessage)
    {
        $this->config['diagnostics']['group']['foo'] = $definition;
        try{
            $res = $this->controller->dispatch(new ConsoleRequest());
        }catch(RuntimeException $e){
            $this->assertStringMatchesFormat($exceptionMessage, $e->getMessage());
            return;
        }
        $this->fail('Definition is invalid!');
    }

    public function testFiltering()
    {
        $this->config['diagnostics']['group1']['test11'] = $test11 = new AlwaysSuccessTest();
        $this->config['diagnostics']['group2']['test21'] = $test21 = new AlwaysSuccessTest();
        $this->config['diagnostics']['group2']['test22'] = $test22 = new AlwaysSuccessTest();
        $this->routeMatch->setParam('testGroupName', 'group2');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(2, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($test21, $test = array_shift($tests));
        $this->assertEquals('group2: test21', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
        $this->assertSame($test22, $test = array_shift($tests));
        $this->assertEquals('group2: test22', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
    }

    /**
     * @depends testModuleProvidedDefinitions
     */
    public function testFilteringByModuleName()
    {
        $this->mm->injectModule('foomodule1', new DummyModule($this->sm));
        $this->mm->injectModule('foomodule2', new DummyModule($this->sm));
        $this->mm->injectModule('foomodule3', new DummyModule($this->sm));
        $this->routeMatch->setParam('testGroupName', 'foomodule2');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(5, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $this->assertInstanceOf('ZFTool\Diagnostics\Test\TestInterface', $test = array_shift($tests));
        $this->assertEquals('foomodule2: test1', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
    }

    public function testBreakOnFailure()
    {
        $this->config['diagnostics']['group']['test1'] = $test1 = new AlwaysSuccessTest();
        $this->config['diagnostics']['group']['test2'] = $test2 = new ReturnThisTest(new Failure());
        $this->config['diagnostics']['group']['test3'] = $test3 = new AlwaysSuccessTest();
        $this->routeMatch->setParam('break', true);
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(2, $results->count());
        $tests = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($test1, $test = array_shift($tests));
        $this->assertEquals('group: test1', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Success', $results[$test]);
        $this->assertSame($test2, $test = array_shift($tests));
        $this->assertEquals('group: test2', $test->getLabel());
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Failure', $results[$test]);
        $this->assertNull(array_shift($tests));
    }

    public function testBasicOutput()
    {
        $this->config['diagnostics']['group']['test1'] = $test1 = new AlwaysSuccessTest();

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertStringMatchesFormat('Starting%a.%aOK%a', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testVerboseOutput()
    {
        $this->config['diagnostics']['group']['test1'] = $test1 = new AlwaysSuccessTest();
        $this->routeMatch->setParam('verbose', true);

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertStringMatchesFormat('Starting%aOK%agroup: test1%aOK (1 diagnostic test%a', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testDebugOutput()
    {
        $this->config['diagnostics']['group']['test1'] = $test1 = new ReturnThisTest(
            new Success('foo', 'bar')
        );
        $this->routeMatch->setParam('debug', true);

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertStringMatchesFormat('Starting%aOK%agroup: test1%afoo%abar%aOK (1 diagnostic test%a', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testQuietMode()
    {
        $this->config['diagnostics']['group']['test1'] = $test1 = new AlwaysSuccessTest();
        $this->routeMatch->setParam('quiet', true);

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertEquals('', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testHttpMode()
    {
        $this->config['diagnostics']['group']['test1'] = $test1 = new AlwaysSuccessTest();

        ob_start();
        $result = $this->controller->dispatch(new \Zend\Http\Request());
        $this->assertEquals('', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZFTool\Diagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testErrorCodes()
    {
        $this->routeMatch->setParam('quiet', true);

        $this->config['diagnostics']['group']['test1'] = $test1 = new AlwaysSuccessTest();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertEquals(0, $result->getErrorLevel());

        $this->config['diagnostics']['group']['test1'] = $test1 = new ReturnThisTest(new Failure());
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertEquals(1, $result->getErrorLevel());

        $this->config['diagnostics']['group']['test1'] = $test1 = new ReturnThisTest(new Warning());
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertEquals(0, $result->getErrorLevel());
    }

    public static function staticTestMethod($message = 'bar', $data = null)
    {
        static::$staticTestMethodCalled = true;
        return new Success($message, $data);
    }

}

function testOutlineFunction($message = 'bar', $data = null)
{
    return new Success($message, $data);
}
