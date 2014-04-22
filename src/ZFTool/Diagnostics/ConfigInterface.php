<?php
namespace ZFTool\Diagnostics;

interface ConfigInterface
{
    /**
     * Should diagnostics stop on first failure.
     *
     * @param  bool $break
     * @return void
     */
    public function setBreakOnFailure($break);

    /**
     * @return bool
     */
    public function getBreakOnFailure();

    /**
     * Get current severity of error that will result in a check failing.
     *
     * @return int
     */
    public function getCatchErrorSeverity();

    /**
     * Set severity of error that will result in a check failing. Defaults to:
     *  E_WARNING|E_PARSE|E_USER_ERROR|E_USER_WARNING|E_RECOVERABLE_ERROR
     *
     * @param int $catchErrorSeverity
     */
    public function setCatchErrorSeverity($catchErrorSeverity);

}
