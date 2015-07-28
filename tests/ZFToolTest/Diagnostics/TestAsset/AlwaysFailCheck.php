<?php
namespace ZFToolTest\Diagnostics\TestAsset;

use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\Failure;

class AlwaysSuccessCheck extends AbstractCheck implements CheckInterface
{
    protected $label = 'Always Fail Check';

    public function check()
    {
        return new Failure();
    }
}
