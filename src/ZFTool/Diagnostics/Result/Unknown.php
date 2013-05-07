<?php
namespace ZFTool\Diagnostics\Result;

use ZFTool\Diagnostics\Result\ResultInterface;

class Unknown implements ResultInterface
{
    /**
     * @var null|string
     */
    protected $message;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @param string|null $message
     * @param mixed       $data
     */
    public function __construct($message = null, $data = null)
    {
        $this->message = $message;
        $this->data = $data;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getData()
    {
        return $this->data;
    }
}
