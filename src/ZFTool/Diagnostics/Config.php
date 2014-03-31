<?php
namespace ZFTool\Diagnostics;

use Zend\Stdlib\AbstractOptions;

class Config extends AbstractOptions implements ConfigInterface
{
    /**
     * @var bool
     */
    protected $breakOnFailure = false;

    /**
     * Severity of error that will result in a test failing. Defaults to:
     *  E_WARNING|E_PARSE|E_USER_ERROR|E_USER_WARNING|E_RECOVERABLE_ERROR
     *
     * @var int
     */
    protected $catchErrorSeverity = 4870;

    /**
     * Should diagnostics stop on first failure.
     *
     * @param boolean $breakOnFailure
     */
    public function setBreakOnFailure($breakOnFailure)
    {
        $this->breakOnFailure = $breakOnFailure;
    }

    /**
     * @return boolean
     */
    public function getBreakOnFailure()
    {
        return $this->breakOnFailure;
    }

    /**
     * Set severity of error that will result in a check failing. Defaults to:
     *  E_WARNING|E_PARSE|E_USER_ERROR|E_USER_WARNING|E_RECOVERABLE_ERROR
     *
     * @param int $catchErrorSeverity
     */
    public function setCatchErrorSeverity($catchErrorSeverity)
    {
        $this->catchErrorSeverity = (int) $catchErrorSeverity;
    }

    /**
     * Get current severity of error that will result in a check failing.
     *
     * @return int
     */
    public function getCatchErrorSeverity()
    {
        return $this->catchErrorSeverity;
    }

}
