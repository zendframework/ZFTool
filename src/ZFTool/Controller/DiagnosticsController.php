<?php

namespace ZFTool\Controller;

use ZFTool\Diagnostics\Exception\RuntimeException;
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

        // TODO: After ZF 2.2.0 is out, remove short flags checks.
        $verbose        = $this->params()->fromRoute('verbose', false) || $this->params()->fromRoute('v', false);
        $debug          = $this->params()->fromRoute('debug', false) || $this->params()->fromRoute('d', false);
        $quiet          = !$verbose && !$debug &&
             ( $this->params()->fromRoute('quiet', false) || $this->params()->fromRoute('q', false) );
        $breakOnFailure = $this->params()->fromRoute('break', false) || $this->params()->fromRoute('b', false);
        $testGroupName  = $this->params()->fromRoute('testGroupName', false);

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

                // Exit the loop early if we found test definitions for
                // the only test group that we want to run.
                if ($testGroupName && $moduleName == $testGroupName) {
                    break;
                }
            }
        }

        // Filter array if a test group name has been provided
        if ($testGroupName) {
            $config = array_intersect_key($config, array($testGroupName => 1));
        }

        // Analyze test definitions and construct test instances
        $testCollection = array();
        foreach ($config as $testGroupName => $tests) {
            foreach ($tests as $testLabel => $test) {
                // Do not use numeric labels.
                if (!$testLabel || is_numeric($testLabel)) {
                    $testLabel = false;
                }

                // Handle a callable.
                if (is_callable($test)) {
                    $test = new Callback($test);
                    if ($testLabel) {
                        $test->setLabel($testGroupName . ': ' . $testLabel);
                    }

                    $testCollection[] = $test;
                    continue;
                }

                // Handle test object instance.
                if (is_object($test)) {
                    if (!$test instanceof TestInterface) {
                        throw new RuntimeException(
                            'Cannot use object of class "' . get_class($test). '" as test. '.
                            'Expected instance of ZFTool\Diagnostics\Test\TestInterface'
                        );

                    }

                    if ($testLabel) {
                        $test->setLabel($testGroupName . ': ' . $testLabel);
                    }
                    $testCollection[] = $test;
                    continue;
                }

                // Handle an array containing callback or identifier with optional parameters.
                if (is_array($test)) {
                    if (!count($test)) {
                        throw new RuntimeException(
                            'Cannot use an empty array() as test definition in "'.$testGroupName.'"'
                        );
                    }

                    // extract test identifier and store the remainder of array as parameters
                    $testName = array_shift($test);
                    $params = $test;

                } elseif (is_scalar($test)) {
                    $testName = $test;
                    $params = array();

                } else {
                    throw new RuntimeException(
                        'Cannot understand diagnostic test definition "' . gettype($test). '" in "'.$testGroupName.'"'
                    );
                }

                // Try to expand test identifier using Service Locator
                if (is_string($testName) && $sm->has($testName)) {
                    $test = $sm->get($testName);

                // Try to use the built-in test class
                } elseif (is_string($testName) && class_exists('ZFTool\Diagnostics\Test\\' . $testName)) {
                    $class = new \ReflectionClass('ZFTool\Diagnostics\Test\\' . $testName);
                    $test = $class->newInstanceArgs($params);

                // Check if provided with a callable inside the array
                } elseif (is_callable($testName)) {
                    $test = new Callback($testName, $params);
                    if ($testLabel) {
                        $test->setLabel($testGroupName . ': ' . $testLabel);
                    }

                    $testCollection[] = $test;
                    continue;

                // Try to expand test using class name
                } elseif (is_string($testName) && class_exists($testName)) {
                    $class = new \ReflectionClass($testName);
                    $test = $class->newInstanceArgs($params);

                } else {
                    throw new RuntimeException(
                        'Cannot find test class or service with the name of "' . $testName . '" ('.$testGroupName.')'
                    );
                }

                if (!$test instanceof TestInterface) {
                    // not a real test
                    throw new RuntimeException(
                        'The test object of class '.get_class($test).' does not implement '.
                        'ZFTool\Diagnostics\Test\TestInterface'
                    );
                }

                // Apply label
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

        if (!$quiet && $this->getRequest() instanceof ConsoleRequest) {
            if ($verbose || $debug) {
                $runner->addReporter(new VerboseConsole($console, $debug));
            } else {
                $runner->addReporter(new BasicConsole($console));
            }
        }

        // Run tests
        $results = $runner->run();

        // Return result
        if ($this->getRequest() instanceof ConsoleRequest) {
            // Return appropriate error code in console
            $model = new ConsoleModel();
            $model->setVariable('results', $results);

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
