<?php
namespace ZFTool\Diagnostics\Test;

use Zend\Stdlib\CallbackHandler;

/**
 * Run a callback function and return result.
 *
 * @package ZFTool\Diagnostics\Test
 */
class Callback extends AbstractTest implements TestInterface {

    /**
     * @var CallbackHandler
     */
    protected $callback;

    public function __construct($callback)
    {
        $this->callback = new CallbackHandler($callback);
    }

    public function run()
    {
        return $this->callback->call();
    }
}