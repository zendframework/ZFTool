<?php
namespace ZFTool\Diagnostics;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Test\TestInterface;
use Zend\EventManager\ResponseCollection;
use Zend\Stdlib\SplStack;

class Result extends SplStack
{

    /**
     * @var bool
     */
    protected $stopped;

    /**
     * Map of labels and corresponding results
     *
     * @var array
     */
    protected $labelResultMap = array();

    /**
     * Map of tests and corresponding results
     *
     * @var array
     */
    protected $testResultMap = array();

    /**
     * Has the testing been stopped before finishing?
     *
     * @return bool
     */
    public function stopped()
    {
        return $this->stopped;
    }

    /**
     * Mark the test as stopped before finishing.
     *
     * @param  bool $flag
     * @return ResponseCollection
     */
    public function setStopped($flag)
    {
        $this->stopped = (bool) $flag;
        return $this;
    }

    /**
     * Convenient access to the first handler return value.
     *
     * @return mixed The first handler return value
     */
    public function first()
    {
        return parent::bottom();
    }

    /**
     * Convenient access to the last handler return value.
     *
     * If the collection is empty, returns null. Otherwise, returns value
     * returned by last handler.
     *
     * @return mixed The last handler return value
     */
    public function last()
    {
        if (count($this) === 0) {
            return null;
        }
        return parent::top();
    }

    /**
     * Check if the given test object or label has been run.
     *
     * @param string|TestInterface $test
     * @throws Exception\InvalidArgumentException
     * @return bool
     */
    public function contains($test)
    {
        if (is_object($test)) {
            if (!$test instanceof TestInterface) {
                throw new InvalidArgumentException(
                    'Cannot check if results contains this test - expected ' .
                     __NAMESPACE__ . '\Test\TestInterface'
                );
            }

            return array_key_exists(spl_object_hash($test), $this->testResultMap);

        } elseif (is_scalar($test)) {
            return array_key_exists($test, $this->labelResultMap);

        } else {
            throw new InvalidArgumentException(
                'Cannot check if results contains ' . gettype($test) .
                ' expected string or ' . __NAMESPACE__ . '\Test\TestInterface'
            );
        }
    }

    /**
     * Retrieve the result of test for given object or label.
     *
     * @param string|TestInterface $test            Label or test object.
     * @throws Exception\InvalidArgumentException
     * @return bool
     */
    public function getResult($test)
    {
        if (is_object($test)) {
            if (!$test instanceof TestInterface) {
                throw new InvalidArgumentException(
                    'Cannot retrieve result for this object - expected ' .
                        __NAMESPACE__ . '\Test\TestInterface'
                );
            }

            if (array_key_exists(spl_object_hash($test), $this->testResultMap)) {
                return $this->testResultMap[spl_object_hash($test)];
            } else {
                return null;
            }

        } elseif (is_scalar($test)) {
            if(array_key_exists($test, $this->labelResultMap)){
                return $this->labelResultMap[$test];
            }
        } else {
            throw new InvalidArgumentException(
                'Cannot retrieve test result for ' . gettype($test) .
                    ' expected string or ' . __NAMESPACE__ . '\Test\TestInterface'
            );
        }
    }

    public function addResult()
    {

    }


}
