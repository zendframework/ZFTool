<?php

namespace ZFTool\Controller;

use ZFTool\Diagnostics\Reporter\BasicConsole;
use ZFTool\Diagnostics\Reporter\VerboseConsole;
use ZFTool\Diagnostics\Runner;
use ZFTool\Diagnostics\Test\Callback;
use ZFTool\Diagnostics\Test\TestInterface;
use Zend\Console\Request as ConsoleRequest;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Version\Version;
use ZFTool\Module;
use Zend\View\Model\ConsoleModel;
use Zend\View\Model\ViewModel;

class DiagnosticsController extends AbstractActionController
{

    public function runAction()
    {
        $sm = $this->getServiceLocator();
        /* @var $console \Zend\Console\Adapter\AdapterInterface */
        /* @var $config array */
        /* @var $mm \Zend\ModuleManager\ModuleManager */
        $console = $sm->get('console');
        $config = $sm->get('Configuration');
        $mm = $sm->get('ModuleManager');

        $breakOnFailure = $this->params()->fromRoute('b', false) || $this->params()->fromRoute('break', false);
        $verbose = $this->params()->fromRoute('v', false) || $this->params()->fromRoute('verbose', false);
        $debug = $this->params()->fromRoute('debug', false);
        $testGroupName = $this->params()->fromRoute('testGroupName', false);

        // Get basic diag configuration
        $config = isset($config['diagnostics']) ? $config['diagnostics'] : array();

        // Collect diag tests from modules
        $modules = $mm->getLoadedModules(false);
        foreach ($modules as $moduleName => $module) {
            if (is_callable(array($module, 'getDiagnostics'))) {
                $tests = $module->getDiagnostics();
                if (is_array($tests)) {
                    $config[$moduleName] = $tests;
                }

                if ($testGroupName && $moduleName == $testGroupName) {
                    break;
                }
            }
        }

        // Filter array if a test group name has been provided
        if ($testGroupName) {
            $config = array_filter($config, function ($val, $key) use (&$testGroupName) {
                return $key == $testGroupName;
            });
        }

        // Analyze test definitions and construct test instances
        $testCollection = array();
        foreach ($config as $testGroupName => $tests) {
            foreach ($tests as $testLabel => $test) {
                if (!$testLabel || is_numeric($testLabel)) {
                    $testLabel = false;
                }

                // a callable
                if (is_callable($test)) {
                    $test = new Callback($test);
                    if ($testLabel) {
                        $test->setLabel($testGroupName . ': ' . $testLabel);
                    }

                    $testCollection[] = $test;
                    continue;
                }

                // handle test object instance
                if (is_object($test)) {
                    if (!$test instanceof TestInterface) {
                        continue; // an unknown object
                    }

                    if ($testLabel) {
                        $test->setLabel($testGroupName . ': ' . $testLabel);
                    }
                    $testCollection[] = $test;
                    continue;
                }

                // handle array containing callback or identifier with optional parameters
                if (is_array($test)) {
                    if (!count($test)) {
                        continue; // empty array
                    }

                    // extract test identifier and store the remainder of array as parameters
                    $testName = array_shift($test);
                    $params = $test;

                    // handle test identifier
                } elseif (is_scalar($test)) {
                    $testName = $test;
                    $params = array();

                } else {
                    continue; // unknown entry
                }

                // Try to expand test identifier using Service Locator
                if ($sm->has($testName)) {
                    $test = $sm->get($testName);

                // Try to expand test using class name
                } elseif (class_exists($testName)) {
                    $class = new \ReflectionClass($testName);
                    $test = $class->newInstanceArgs($params);

                // Try to use the built-in test class
                } elseif (class_exists('ZFTool\Diagnostics\Test\\' . $testName)) {
                    $class = new \ReflectionClass('ZFTool\Diagnostics\Test\\' . $testName);
                    $test = $class->newInstanceArgs($params);

                // Try to find built-in function with that name
                } elseif (function_exists($testName)) {
                    $test = new Callback(function() use ($testName, $params){
                        return call_user_func_array($testName, $params);
                    });

                } else {
                    continue; // unable to find test
                }

                if (!$test instanceof TestInterface) {
                    continue; // not a real test
                }

                if ($testLabel) {
                    $test->setLabel($testGroupName . ': ' . $testLabel);
                }

                $testCollection[] = $test;
            }
        }

        // Configure test runner
        $runner = new Runner();
        $runner->addTests($testCollection);
        $runner->getConfig()->setBreakOnFailure($breakOnFailure);

        if ($this->getRequest() instanceof ConsoleRequest) {
            if ($verbose || $debug) {
                $runner->addReporter(new VerboseConsole($console, $debug));
            } else {
                $runner->addReporter(new BasicConsole($console));
            }
        }

        // Run tests
        $results = $runner->run();

        if ($this->getRequest() instanceof ConsoleRequest) {
            // Return appropriate error code in console
            $model = new ConsoleModel();

            if ($results->getFailureCount() > 0) {
                $model->setErrorLevel(1);
            } else {
                $model->setErrorLevel(0);
            }
        } else {
            // Display results as a web page
            $model = new ViewModel();
            $model->setVariable('results', $results);
        }

        return $model;
    }

}
