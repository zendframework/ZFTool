<?php
namespace ZFTool\Diagnostics\Test;

use Zend\Stdlib\CallbackHandler;

/**
 * Run a callback function and return result.
 *
 * @package ZFTool\Diagnostics\Test
 */
class Callback extends AbstractTest implements TestInterface
{
    /**
     * @var CallbackHandler
     */
    protected $callback;

    /**
     * @var array
     */
    protected $params = array();

    public function __construct($callback, $params = array())
    {
        $this->callback = new CallbackHandler($callback);
        $this->params = $params;
    }

    public function run()
    {
        return $this->callback->call($this->params);
    }
}
