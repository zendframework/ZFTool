<?php
namespace ZFTool\Diagnostics\Result;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Test\TestInterface;

class Collection extends \ArrayIterator
{
    protected $countSuccess = 0;
    protected $countWarning = 0;
    protected $countFailure = 0;
    protected $objMap = array();

    /**
     * Get number of successful test results.
     *
     * @return int
     */
    public function getSuccessCount()
    {
        return $this->countSuccess;
    }

    /**
     * Get number of failed test results.
     *
     * @return int
     */
    public function getFailureCount()
    {
        return $this->countFailure;
    }

    /**
     * Get number of warnings.
     *
     * @return int
     */
    public function getWarningCount()
    {
        return $this->countWarning;
    }

    public function offsetGet($index)
    {
        $index = $this->validateIndex($index);
        return parent::offsetGet($index);
    }

    public function offsetExists($index)
    {
        $index = $this->validateIndex($index);
        return parent::offsetExists($index);
    }

    public function offsetSet($index, $testResult)
    {
        $indexObj = $index;
        $index = $this->validateIndex($index);
        $testResult = $this->validateValue($testResult);

        // Decrement counters when replacing existing item
        if(parent::offsetExists($index)) {
            $oldResult = parent::offsetGet($index);
            if($oldResult instanceof Success) {
                $this->countSuccess--;
            }elseif($oldResult instanceof Failure) {
                $this->countFailure--;
            }elseif($oldResult instanceof Warning) {
                $this->countWarning--;
            }
        }

        // store a reference to test in internal map for future iteration
        $this->objMap[$index] = $indexObj;

        parent::offsetSet($index, $testResult);

        // Increment counters
        if($testResult instanceof Success) {
            $this->countSuccess++;
        }elseif($testResult instanceof Failure) {
            $this->countFailure++;
        }elseif($testResult  instanceof Warning) {
            $this->countWarning++;
        }
    }

    public function offsetUnset($index)
    {
        $index = $this->validateIndex($index);

        // Decrement counters when replacing existing item
        if(parent::offsetExists($index)) {
            $oldResult = parent::offsetGet($index);
            if($oldResult instanceof Success) {
                $this->countSuccess--;
            }elseif($oldResult instanceof Failure) {
                $this->countFailure--;
            }elseif($oldResult instanceof Warning) {
                $this->countWarning--;
            }
        }

        parent::offsetUnset($index);
    }

    public function exchangeArray($array)
    {
        // Validate each element in the target array
        foreach($array as $test => $testResult)
        {
            $this->validateIndex($test);
            $this->validateValue($testResult);
        }

        return parent::exchangeArray($array);
    }

    public function key()
    {
        return $this->objMap[parent::key()];
    }

    public function seek($position)
    {
        $position = $this->validateIndex($position);
        parent::seek($position);
    }

    /**
     * Convert an object to a hash that can be queried in the collection.
     *
     * @param mixed $index
     * @return string
     * @throws \ZFTool\Diagnostics\Exception\InvalidArgumentException
     */
    protected function validateIndex($index)
    {
        if (!is_object($index) || !$index instanceof TestInterface) {
            $what = is_object($index) ? 'object of type ' . get_class($index) : gettype($index);
            throw new InvalidArgumentException(
                'Cannot use '. $what.' as index for this collection. Expected instance of TestInterface.'
            );
        }

        return spl_object_hash($index);
    }

    /**
     * Validate if the value can be stored in this collection.
     *
     * @param mixed $testResult
     * @return mixed
     * @throws \ZFTool\Diagnostics\Exception\InvalidArgumentException
     */
    protected function validateValue($testResult)
    {
        if(!is_object($testResult) || !$testResult instanceof ResultInterface) {
            $what = is_object($testResult) ? 'object of type ' . get_class($testResult) : gettype($testResult);
            throw new InvalidArgumentException(
                'This collection cannot hold '  .$what. ' Expected instance of ' . __NAMESPACE__ . '\ResultInterface'
            );
        }

        return $testResult;
    }
}