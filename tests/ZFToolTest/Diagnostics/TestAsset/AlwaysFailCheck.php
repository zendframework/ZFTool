<?php
namespace ZFToolTest\Diagnostics\TestAsset;

use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Result\Failure;

/**
 * This check will always return fail
 */
class AlwaysFailCheck extends AbstractCheck
{
    protected $label = 'Always Fail Check';

    public function check()
    {
        return new Failure();
    }
}
