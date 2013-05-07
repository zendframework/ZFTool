<?php
namespace ZFTool\Diagnostics\Result;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Test\TestInterface;

class Collection extends \SplObjectStorage
{
    protected $countSuccess = 0;
    protected $countWarning = 0;
    protected $countFailure = 0;
    protected $countUnknown = 0;
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

    /**
     * Get number of unknown results.
     *
     * @return int
     */
    public function getUnknownCount()
    {
        return $this->countUnknown;
    }

    public function offsetGet($index)
    {
        $this->validateIndex($index);

        return parent::offsetGet($index);
    }

    public function offsetExists($index)
    {
        $this->validateIndex($index);

        return parent::offsetExists($index);
    }

    public function offsetSet($index, $testResult)
    {
        $indexObj = $index;
        $this->validateIndex($index);
        $this->validateValue($testResult);

        // Decrement counters when replacing existing item
        if (parent::offsetExists($index)) {
            $oldResult = parent::offsetGet($index);
            if ($oldResult instanceof Success) {
                $this->countSuccess--;
            } elseif ($oldResult instanceof Failure) {
                $this->countFailure--;
            } elseif ($oldResult instanceof Warning) {
                $this->countWarning--;
            } else {
                $this->countUnknown--;
            }
        }

        parent::offsetSet($index, $testResult);

        // Increment counters
        if ($testResult instanceof Success) {
            $this->countSuccess++;
        } elseif ($testResult instanceof Failure) {
            $this->countFailure++;
        } elseif ($testResult  instanceof Warning) {
            $this->countWarning++;
        } else {
            $this->countUnknown++;
        }
    }

    public function offsetUnset($index)
    {
        $this->validateIndex($index);

        // Decrement counters when replacing existing item
        if (parent::offsetExists($index)) {
            $oldResult = parent::offsetGet($index);
            if ($oldResult instanceof Success) {
                $this->countSuccess--;
            } elseif ($oldResult instanceof Failure) {
                $this->countFailure--;
            } elseif ($oldResult instanceof Warning) {
                $this->countWarning--;
            } else {
                $this->countUnknown--;
            }
        }

        parent::offsetUnset($index);
    }

    /**
     * Validate index object.
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
                'Cannot use ' . $what . ' as index for this collection. Expected instance of TestInterface.'
            );
        }

        return $index;
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
        if (!is_object($testResult) || !$testResult instanceof ResultInterface) {
            $what = is_object($testResult) ? 'object of type ' . get_class($testResult) : gettype($testResult);
            throw new InvalidArgumentException(
                'This collection cannot hold ' . $what . ' Expected instance of ' . __NAMESPACE__ . '\ResultInterface'
            );
        }

        return $testResult;
    }
}
