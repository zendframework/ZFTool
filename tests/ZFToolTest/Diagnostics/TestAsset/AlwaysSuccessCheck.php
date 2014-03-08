<?php
namespace ZFToolTest\Diagnostics\TestAsset;

use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Check\CheckInterface;

class AlwaysSuccessCheck extends AbstractCheck implements CheckInterface
{
    protected $label = 'Always Successful Check';

    public function check()
    {
        return new Success();
    }
}
