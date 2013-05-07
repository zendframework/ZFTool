<?php
namespace ZFTool\Diagnostics;

interface ConfigInterface
{
    /**
     * Should diagnostics stop on first failure.
     *
     * @param bool $break
     * @return void
     */
    public function setBreakOnFailure($break);

    /**
     * @return bool
     */
    public function getBreakOnFailure();

    /**
     * @return string
     */
    public function getDefaultRunListenerClass();

    /**
     * Set the default class to create run listener.
     *
     * @param string $defaultRunListenerClass
     */
    public function setDefaultRunListenerClass($defaultRunListenerClass);
}
