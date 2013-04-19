<?php
namespace ZFTool\Diagnostics;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Exception\RuntimeException;
use ZFTool\Diagnostics\Result\Collection;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\ResultInterface;
use ZFTool\Diagnostics\Test\TestInterface as Test;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use ArrayObject;

class Runner
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * An array of tests to run
     *
     * @var ArrayObject
     */
    protected $tests;

    public function __construct(ConfigInterface $config = null)
    {
        // init config
        if ($config === null) {
            $config = new Config();
        }

        $this->config = $config;
        $this->tests = new ArrayObject();

        // Add default run listener
        $listenerClass = $this->getConfig()->getDefaultRunListenerClass();
        if ($listenerClass) {
            $this->getEventManager()->attachAggregate(new $listenerClass());
        }
    }

    /**
     * @throws Exception\RuntimeException
     * @return Collection The result of tests
     */
    public function run()
    {
        $breakOnFailure = $this->getConfig()->getBreakOnFailure();
        $em = $this->getEventManager();
        $testRun = new RunEvent();
        $results = new Collection();
        $testRun->setResults($results);
        $testRun->setParam('tests', $this->tests);

        // trigger START event
        $em->trigger(RunEvent::EVENT_START, $testRun);

        // Iterate over all tests
        foreach ($this->tests as $test) {
            $testRun->setTarget($test);
            $testRun->clearLastResult();

            // Skip testing if BEFORE_RUN returned false or has been stopped
            $beforeRun = $em->trigger(RunEvent::EVENT_BEFORE_RUN, $testRun);
            if ($beforeRun->stopped() || $beforeRun->contains(false)) {
                continue;
            }

            // Run the test!
            $result = $em->trigger(RunEvent::EVENT_RUN, $testRun, function($r){
                if($r instanceof ResultInterface) {
                    return true;
                } else {
                    return false;
                }
            })->last();

            // Interpret result
            if (!is_object($result) || !$result instanceof ResultInterface) {
                $what = is_object($result) ? 'object of class ' . get_class($result) : gettype($result);
                throw new RuntimeException(
                    'Test run resulted in ' . $what . ' Expected instance of ' . __NAMESPACE__ . '\Result\ResultInterface'
                );
            }

            // Save test result
            $results[$test] = $result;
            $testRun->setLastResult($result);

            // Stop testing if AFTER_RUN returned false or has been stopped
            $afterRun = $em->trigger(RunEvent::EVENT_AFTER_RUN, $testRun);
            if ($afterRun->stopped() || $afterRun->contains(false)) {
                $em->trigger(RunEvent::EVENT_STOP, $testRun);
                break;
            }

            // Stop testing on first failure
            if ($breakOnFailure && $result instanceof Failure) {
                $em->trigger(RunEvent::EVENT_STOP, $testRun);
                break;
            }
        }

        // trigger FINISH event
        $em->trigger(RunEvent::EVENT_FINISH, $testRun);

        return $results;
    }

    /**
     * @param ConfigInterface $config
     */
    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Add diagnostic test to run.
     *
     * @param Test        $test
     */
    public function addTest(Test $test)
    {
        $this->tests[] = $test;
    }

    /**
     * @param array|\Traversable $tests
     * @throws Exception\InvalidArgumentException
     */
    public function addTests($tests)
    {
        if (!is_array($tests) && !$tests instanceof \Traversable) {
            $what = is_object($tests) ? 'object of class '.get_class($tests) : gettype($tests);
            throw new InvalidArgumentException('Cannot add tests from '.$what.' - expected array or Traversable');
        }

        foreach($tests as $test) {
            if (!$test instanceof Test ){
                $what = is_object($test) ? 'object of class '.get_class($test) : gettype($test);
                throw new InvalidArgumentException('Cannot use '.$what.' as test - expected '.__NAMESPACE__. '\Test\TestInterface');
            }
            $this->tests[] = $test;
        }
    }

    /**
     * Add new reporter.
     *
     * @param ListenerAggregateInterface $reporter
     */
    public function addReporter(ListenerAggregateInterface $reporter)
    {
        $this->getEventManager()->attachAggregate($reporter);
    }

    /**
     * Remove previously attached reporter.
     *
     * @param ListenerAggregateInterface $reporter
     */
    public function removeReporter(ListenerAggregateInterface $reporter)
    {
        $this->getEventManager()->detachAggregate($reporter);
    }

    /**
     * @param \Zend\EventManager\EventManagerInterface $em
     */
    public function setEventManager(EventManagerInterface $em)
    {
        $this->eventManager = $em;
    }

    /**
     * @return \Zend\EventManager\EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->eventManager) {
            $this->eventManager = new EventManager();
        }

        return $this->eventManager;
    }

    /**
     * @return ArrayObject
     */
    public function getTests()
    {
        return $this->tests;
    }

}
