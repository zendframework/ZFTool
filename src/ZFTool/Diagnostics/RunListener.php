<?php
namespace ZFTool\Diagnostics;


use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\ResultInterface;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;;
use Zend\Stdlib\ErrorHandler;
use ErrorException;

class RunListener implements ListenerAggregateInterface
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
        $this->listeners[] = $events->attach(RunEvent::EVENT_RUN, array($this, 'onRun'));
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

    public function onRun(RunEvent $e)
    {
        /* @var $test \ZFTool\Diagnostics\Test\TestInterface */
        $test = $e->getTarget();

        try {
            ErrorHandler::start();
            $result = $test->run();
            ErrorHandler::stop(true);

        } catch (ErrorException $e) {
            return new Failure(
                'PHP ' . static::getSeverityDescription($e->getSeverity()) . ': ' . $e->getMessage(),
                $e
            );
        } catch (\Exception $e) {
            return new Failure(
                'Uncaught ' . get_class($e) . ': ' . $e->getMessage(),
                $e
            );
        }

        // Check result
        if (is_object($result)) {
            if ($result instanceof ResultInterface) {
                return $result;
            } else {
                return new Failure(
                    'Test returned unknown object ' . get_class($result)
                );
            }
        } elseif (is_bool($result)) {
            if ($result) {
                return new Success();
            } else {
                return new Failure();

            }
        } elseif (is_scalar($result)) {
            return new Warning((string)$result);
        } else {
            return new Failure(
                'Test returned ' . gettype($result)
            );
        }
    }

    /**
     * Convert PHP error severity INT to name.
     *
     * @param integer $severity
     * @return string
     */
    public static function getSeverityDescription($severity)
    {
        switch ($severity) {
            case E_ERROR: // 1 //
                return 'ERROR';
            case E_WARNING: // 2 //
                return 'WARNING';
            case E_PARSE: // 4 //
                return 'PARSE';
            case E_NOTICE: // 8 //
                return 'NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'USER_DEPRECATED';
            default:
                return 'error severity ' . $severity;
        }
    }

}
