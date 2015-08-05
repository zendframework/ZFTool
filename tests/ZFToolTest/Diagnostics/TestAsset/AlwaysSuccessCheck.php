<?php
namespace ZFToolTest\Diagnostics\TestAsset;

use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Check\AbstractCheck;

/**
 * This check will always return success
 */
class AlwaysSuccessCheck extends AbstractCheck
{
    protected $label = 'Always Successful Check';

    public function check()
    {
        return new Success();
    }
}
