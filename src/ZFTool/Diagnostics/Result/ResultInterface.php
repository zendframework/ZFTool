<?php
namespace ZFTool\Diagnostics\Result;

interface ResultInterface
{
    /**
     * Get message related to the result.
     *
     * @return string
     */
    public function getMessage();


    /**
     * Get detailed data related to the test result.
     * @return mixed
     */
    public function getData();
}
