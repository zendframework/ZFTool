<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Thinkscape
 * Date: 28.03.2013
 * Time: 10:08 AM
 * To change this template use File | Settings | File Templates.
 */

namespace ZFTool\Diagnostics\Result;


class Failure implements ResultInterface
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
