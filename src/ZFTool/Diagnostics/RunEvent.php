<?php
namespace ZFTool\Diagnostics;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Result\Collection;
use ZFTool\Diagnostics\Result\ResultInterface;
use ZFTool\Diagnostics\Test\TestInterface;
use Zend\EventManager\Event;

class RunEvent extends Event
{
    /**#@+
     * Events triggered by Runner
     */
    const EVENT_START       = 'start';
    const EVENT_BEFORE_RUN  = 'before.run';
    const EVENT_RUN         = 'run';
    const EVENT_AFTER_RUN   = 'after.run';
    const EVENT_FINISH      = 'finish';
    const EVENT_STOP        = 'stop';
    /**#@-*/

    /**
     * @var string
     */
    protected $label;

    /**
     * @var Collection
     */
    protected $results;

    /**
     * @var \ZFTool\Diagnostics\Result\ResultInterface
     */
    protected $lastResult;

    /**
     * @param \ZFTool\Diagnostics\Result\Collection $resultCollection
     */
    public function setResults(Collection $resultCollection)
    {
        $this->results = $resultCollection;
    }

    /**
     * @return \ZFTool\Diagnostics\Result\Collection
     */
    public function getResults()
    {
        return $this->results;
    }

    public function setLastResult(ResultInterface $result)
    {
        $this->lastResult = $result;
    }

    /**
     * Get the result of last test.
     *
     * @return null|\ZFTool\Diagnostics\Result\ResultInterface
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }


    public function clearLastResult()
    {
        $this->lastResult = null;
    }


    public function setTarget($test)
    {
        if(!$test instanceof TestInterface){
            $what = is_object($test) ? 'object of class '.get_class($test) : gettype($test);
            throw new InvalidArgumentException('Cannot use '.$what.' as a target for the test');
        }
        return parent::setTarget($test);
    }


}
