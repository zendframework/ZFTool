<?php
namespace ZFTool\Diagnostics\Reporter;

use ArrayObject;
use ZFTool\Diagnostics\RunEvent;
use ZFTool\Diagnostics\Result\Unknown;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

abstract class AbstractReporter implements ListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Attach listeners to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(RunEvent::EVENT_START,     array($this, 'onStart'));
        $this->listeners[] = $events->attach(RunEvent::EVENT_BEFORE_RUN,array($this, 'onBeforeRun'));
        $this->listeners[] = $events->attach(RunEvent::EVENT_RUN,       array($this, 'onRun'));
        $this->listeners[] = $events->attach(RunEvent::EVENT_AFTER_RUN, array($this, 'onAfterRun'));
        $this->listeners[] = $events->attach(RunEvent::EVENT_FINISH,    array($this, 'onFinish'));
        $this->listeners[] = $events->attach(RunEvent::EVENT_STOP,      array($this, 'onStop'));
    }

    /**
     * Detach listeners from an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * @param  RunEvent $e
     * @return mixed
     */
    public function onStart(RunEvent $e){}

    /**
     * @param  RunEvent $e
     * @return mixed
     */
    public function onBeforeRun(RunEvent $e){}

    /**
     * @param  RunEvent $e
     * @return mixed
     */
    public function onRun(RunEvent $e){}

    /**
     * @param  RunEvent $e
     * @return mixed
     */
    public function onAfterRun(RunEvent $e){}

    /**
     * @param  RunEvent $e
     * @return mixed
     */
    public function onFinish(RunEvent $e){}

    /**
     * @param  RunEvent $e
     * @return mixed
     */
    public function onStop(RunEvent $e){}
}
