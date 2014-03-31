<?php
namespace ZFToolTest\Diagnostics\Check;

use Zend\Console\Request as ConsoleRequest;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayObject;
use Zend\Stdlib\ArrayUtils;
use ZendDiagnostics\Result\Collection;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;
use ZendDiagnostics\Check\Callback;
use ZFTool\Controller\DiagnosticsController;
use ZFTool\Diagnostics\Exception\RuntimeException;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessCheck;
use ZFToolTest\Diagnostics\TestAsset\ReturnThisCheck;
use ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter;
use ZFToolTest\DummyModule;
use ZFToolTest\TestAsset\InjectableModuleManager;

require_once __DIR__.'/TestAsset/ConsoleAdapter.php';
require_once __DIR__.'/TestAsset/InjectableModuleManager.php';
require_once __DIR__.'/TestAsset/ReturnThisCheck.php';
require_once __DIR__.'/TestAsset/AlwaysSuccessCheck.php';
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

        // Top-level output buffering to prevent leaking info to the console
        ob_start();
    }

    public function teardown()
    {
        // Discard any output from the diag controller
        ob_end_clean();
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
            'an invalid check instance' => array(
                new \stdClass(),
                'Cannot use object of class "stdClass"%a'
            ),
            'an unknown definition type' => array(
                $res,
                'Cannot understand diagnostic check definition %a'
            ),
            'an invalid class name' => array(
                'stdClass',
                'The check object of class stdClass does not implement ZendDiagnostics\Check\CheckInterface'
            ),
            'an unknown check identifier' => array(
                'some\unknown\class\or\service\identifier',
                'Cannot find check class or service with the name of "some\unknown\class\or\service\identifier"%a'
            )
        );
    }

    public function testNoChecks()
    {
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertEquals(1, $result->getErrorLevel());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => new Check()
     *      )
     *  )
     */
    public function testConfigBasedTestInstance()
    {
        $expectedResult = new Success('bar');
        $check = new ReturnThisCheck($expectedResult);
        $this->config['diagnostics']['group']['foo'] = $check;
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        /** @var Collection $results */
        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $this->assertTrue($results->offsetExists($check));
        $this->assertSame($expectedResult, $results[$check]);
        $this->assertSame('group: foo', $check->getLabel());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => 'My\Namespace\ClassName'
     *      )
     *  )
     */
    public function testConfigBasedTestClassName()
    {
        $this->config['diagnostics']['group']['foo'] = 'ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessCheck';
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $check = array_pop($checks);

        $this->assertInstanceOf('ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessCheck', $check);
        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => array('My\Namespace\ClassName', 'methodName')
     *      )
     *  )
     */
    public function testConfigBasedStaticMethod()
    {
        static::$staticTestMethodCalled = false;
        $this->config['diagnostics']['group']['foo'] = array(__CLASS__, 'staticTestMethod');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $check = array_pop($checks);

        $this->assertInstanceOf('ZendDiagnostics\Check\Callback', $check);
        $this->assertTrue(static::$staticTestMethodCalled);
        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
        $this->assertEquals('bar', $results[$check]->getMessage());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => array(
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
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $check = array_pop($checks);

        $this->assertInstanceOf('ZendDiagnostics\Check\Callback', $check);
        $this->assertTrue(static::$staticTestMethodCalled);
        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
        $this->assertEquals($expectedMessage, $results[$check]->getMessage());
        $this->assertEquals($expectedData, $results[$check]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => 'someFunctionName'
     *      )
     *  )
     */
    public function testConfigBasedFunction()
    {
        $this->config['diagnostics']['group']['foo'] = __NAMESPACE__ . '\testOutlineFunction';
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $check = array_pop($checks);

        $this->assertInstanceOf('ZendDiagnostics\Check\Callback', $check);
        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
        $this->assertEquals('bar', $results[$check]->getMessage());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => array('someFunctionName', 'param1', 'param2')
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
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $check = array_pop($checks);

        $this->assertInstanceOf('ZendDiagnostics\Check\Callback', $check);
        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
        $this->assertEquals($expectedMessage, $results[$check]->getMessage());
        $this->assertEquals($expectedData, $results[$check]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => array('ClassExists', 'params')
     *      )
     *  )
     */
    public function testConfigBasedBuiltinTest()
    {
        $this->config['diagnostics']['group']['foo'] = array('ClassExists', __CLASS__);
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $check = array_pop($checks);

        $this->assertInstanceOf('ZendDiagnostics\Check\ClassExists', $check);
        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => 'Some\ServiceManager\Identifier'
     *      )
     *  ),
     *  'service_manager' => array(
     *      'invokables' => array(
     *          'Some\ServiceManager\Identifier' => 'Some\Check\Class'
     *      )
     *  )
     */
    public function testConfigBasedServiceName()
    {
        $expectedData = mt_rand(1,PHP_INT_MAX);
        $expectedMessage = mt_rand(1,PHP_INT_MAX);
        $check = new Callback(function () use ($expectedMessage, $expectedData) {
            return new Success($expectedMessage, $expectedData);
        });
        $this->sm->setService('ZFToolTest\TestService', $check);

        $this->config['diagnostics']['group']['foo'] = 'ZFToolTest\TestService';

        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($check, array_pop($checks));

        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
        $this->assertEquals($expectedMessage, $results[$check]->getMessage());
        $this->assertEquals($expectedData, $results[$check]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *          'check label' => 'PhpVersion'
     *      )
     *  )
     */
    public function testBuiltInBeforeCallable()
    {
        $this->config['diagnostics']['group']['foo'] = array('PhpVersion', '1.0.0');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $check = array_pop($checks);

        $this->assertInstanceOf('ZendDiagnostics\Check\PhpVersion', $check);
    }

    public function testModuleProvidedDefinitions()
    {
        $module = new DummyModule($this->sm);
        $this->mm->injectModule('dummymodule',$module);
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(5, $results->count());

        $expected = array(
            array('dummymodule: test1', 'ZendDiagnostics\Result\Success', 'test1 success'),
            array('dummymodule: test2', 'ZendDiagnostics\Result\Success', ''),
            array('dummymodule: test3', 'ZendDiagnostics\Result\Failure', ''),
            array('dummymodule: test4', 'ZendDiagnostics\Result\Failure', 'static check message'),
            array('dummymodule: test5', 'ZendDiagnostics\Result\Failure', 'someOtherMessage'),
        );

        $x = 0;
        foreach ($results as $check) {
            $result = $results[$check];
            list($label, $class, $message) = $expected[$x++];
            error_reporting(E_ERROR);
            $this->assertInstanceOf('ZendDiagnostics\Check\CheckInterface', $check);
            $this->assertEquals($label,   $check->getLabel());
            $this->assertEquals($message, $result->getMessage());
            $this->assertInstanceOf($class, $result);
        }
    }

    public function testTriggerAWarning()
    {
        $check = new Callback(function () {
            1/0; // < throw a warning
        });

        $this->config['diagnostics']['group']['foo'] = $check;

        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($check, array_pop($checks));

        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
    }

    public function testThrowingAnException()
    {
        $e = new \Exception();
        $check = new Callback(function () use (&$e) {
            throw $e;
        });

        $this->config['diagnostics']['group']['foo'] = $check;

        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(1, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($check, array_pop($checks));

        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
        $this->assertSame($e, $results[$check]->getData());
    }

    public function testInvalidResult()
    {
        $someObj = new \stdClass;
        $check = new ReturnThisCheck($someObj);
        $this->config['diagnostics']['group']['foo'] = $check;

        $dispatchResult = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $dispatchResult);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $dispatchResult->getVariable('results'));
        $results = $dispatchResult->getVariable('results');
        $this->assertEquals(1, $results->count());
        $check = array_pop(ArrayUtils::iteratorToArray(($results)));
        $this->assertSame('group: foo', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
        $this->assertSame($someObj, $results[$check]->getData());

        $someResource = fopen('php://memory','r');
        fclose($someResource);
        $check = new ReturnThisCheck($someResource);
        $this->config['diagnostics']['group']['foo'] = $check;
        $dispatchResult = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $dispatchResult);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $dispatchResult->getVariable('results'));
        $results = $dispatchResult->getVariable('results');
        $check = array_pop(ArrayUtils::iteratorToArray(($results)));
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
        $this->assertSame($someResource, $results[$check]->getData());

        $check = new ReturnThisCheck(123);
        $this->config['diagnostics']['group']['foo'] = $check;
        $dispatchResult = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $dispatchResult);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $dispatchResult->getVariable('results'));
        $results = $dispatchResult->getVariable('results');
        $check = array_pop(ArrayUtils::iteratorToArray(($results)));
        $this->assertInstanceOf('ZendDiagnostics\Result\Warning', $results[$check]);
        $this->assertEquals(123, $results[$check]->getData());
    }

    /**
     *  'diagnostics' => array(
     *      'group' => array(
     *           'Some\Check',
     *           'Some\Other\Check',
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
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(3, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));

        $check = array_shift($checks);
        $this->assertInstanceOf('ZendDiagnostics\Check\ClassExists', $check);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);

        $check = array_shift($checks);
        $this->assertInstanceOf('ZendDiagnostics\Check\ClassExists', $check);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);

        $check = array_shift($checks);
        $this->assertInstanceOf('ZendDiagnostics\Check\ClassExists', $check);
        $this->assertSame('group: test3', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
    }

    /**
     * @dataProvider invalidDefinitionsProvider
     */
    public function testInvalidDefinitions($definition, $exceptionMessage)
    {
        $this->config['diagnostics']['group']['foo'] = $definition;
        try {
            $res = $this->controller->dispatch(new ConsoleRequest());
        } catch (RuntimeException $e) {
            $this->assertStringMatchesFormat($exceptionMessage, $e->getMessage());

            return;
        }
        $this->fail('Definition is invalid!');
    }

    public function testFiltering()
    {
        $this->config['diagnostics']['group1']['test11'] = $check11 = new AlwaysSuccessCheck();
        $this->config['diagnostics']['group2']['test21'] = $check21 = new AlwaysSuccessCheck();
        $this->config['diagnostics']['group2']['test22'] = $check22 = new AlwaysSuccessCheck();
        $this->routeMatch->setParam('filter', 'group2');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(2, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($check21, $check = array_shift($checks));
        $this->assertEquals('group2: test21', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
        $this->assertSame($check22, $check = array_shift($checks));
        $this->assertEquals('group2: test22', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
    }

    /**
     * @depends testModuleProvidedDefinitions
     */
    public function testFilteringByModuleName()
    {
        $this->mm->injectModule('foomodule1', new DummyModule($this->sm));
        $this->mm->injectModule('foomodule2', new DummyModule($this->sm));
        $this->mm->injectModule('foomodule3', new DummyModule($this->sm));
        $this->routeMatch->setParam('filter', 'foomodule2');
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(5, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $this->assertInstanceOf('ZendDiagnostics\Check\CheckInterface', $check = array_shift($checks));
        $this->assertEquals('foomodule2: test1', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
    }

    public function testFilteringFailure()
    {
        $this->config['diagnostics']['group1']['test11'] = $check11 = new AlwaysSuccessCheck();
        $this->config['diagnostics']['group2']['test21'] = $check21 = new AlwaysSuccessCheck();
        $this->config['diagnostics']['group2']['test22'] = $check22 = new AlwaysSuccessCheck();
        $this->routeMatch->setParam('filter', 'non-existent-group');
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertEquals(1, $result->getErrorLevel());
    }

    public function testBreakOnFailure()
    {
        $this->config['diagnostics']['group']['test1'] = $check1 = new AlwaysSuccessCheck();
        $this->config['diagnostics']['group']['test2'] = $check2 = new ReturnThisCheck(new Failure());
        $this->config['diagnostics']['group']['test3'] = $check3 = new AlwaysSuccessCheck();
        $this->routeMatch->setParam('break', true);
        $result = $this->controller->dispatch(new ConsoleRequest());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));

        $results = $result->getVariable('results');
        $this->assertEquals(2, $results->count());
        $checks = ArrayUtils::iteratorToArray(($results));
        $this->assertSame($check1, $check = array_shift($checks));
        $this->assertEquals('group: test1', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $results[$check]);
        $this->assertSame($check2, $check = array_shift($checks));
        $this->assertEquals('group: test2', $check->getLabel());
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
        $this->assertNull(array_shift($checks));
    }

    public function testBasicOutput()
    {
        $this->config['diagnostics']['group']['test1'] = $check1 = new AlwaysSuccessCheck();

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertStringMatchesFormat('Starting%a.%aOK%a', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testVerboseOutput()
    {
        $this->config['diagnostics']['group']['test1'] = $check1 = new AlwaysSuccessCheck();
        $this->routeMatch->setParam('verbose', true);

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertStringMatchesFormat('Starting%aOK%agroup: test1%aOK (1 diagnostic check%a', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testDebugOutput()
    {
        $this->config['diagnostics']['group']['test1'] = $check1 = new ReturnThisCheck(
            new Success('foo', 'bar')
        );
        $this->routeMatch->setParam('debug', true);

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertStringMatchesFormat('Starting%aOK%agroup: test1%afoo%abar%aOK (1 diagnostic check%a', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testQuietMode()
    {
        $this->config['diagnostics']['group']['test1'] = $check1 = new AlwaysSuccessCheck();
        $this->routeMatch->setParam('quiet', true);

        ob_start();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertEquals('', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testHttpMode()
    {
        $this->config['diagnostics']['group']['test1'] = $check1 = new AlwaysSuccessCheck();

        ob_start();
        $result = $this->controller->dispatch(new \Zend\Http\Request());
        $this->assertEquals('', ob_get_clean());

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result->getVariable('results'));
    }

    public function testErrorCodes()
    {
        $this->routeMatch->setParam('quiet', true);

        $this->config['diagnostics']['group']['test1'] = $check1 = new AlwaysSuccessCheck();
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertEquals(0, $result->getErrorLevel());

        $this->config['diagnostics']['group']['test1'] = $check1 = new ReturnThisCheck(new Failure());
        $result = $this->controller->dispatch(new ConsoleRequest());
        $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $result);
        $this->assertEquals(1, $result->getErrorLevel());

        $this->config['diagnostics']['group']['test1'] = $check1 = new ReturnThisCheck(new Warning());
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
