<?php
namespace ZFTool\Diagnostics;

use Traversable;
use Zend\ServiceManager\ConfigInterface;
use ZendDiagnostics\Runner\Reporter\ReporterInterface;
use ZendDiagnostics\Runner\Runner as ZendDiagnosticsRunner;
use ZendDiagnostics\Result\Collection as ResultsCollection;

class Runner extends ZendDiagnosticsRunner
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * Create new instance of Runner, optionally providing configuration and initial collection of Checks.
     *
     * @param ConfigInterface        $config   Config settings.
     * @param null|array|Traversable $checks   A collection of Checks to run.
     * @param null|ReporterInterface $reporter Reporter instance to use
     */
    public function __construct(ConfigInterface $config = null, $checks = null, ReporterInterface $reporter = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }

        return parent::__construct(array(), $checks, $reporter);
    }

    /**
     * Run all Checks and return a Result\Collection for every check.
     *
     * @param  string|null $checkAlias An alias of Check instance to run, or null to run all checks.
     * @return ResultsCollection The result of running Checks
     */
    public function run($checkAlias = null)
    {
        $this->breakOnFailure = $this->config->getBreakOnFailure();
        $this->catchErrorSeverity = $this->config->getBreakOnFailure();
        return parent::run($checkAlias);
    }

    /**
     * @param ConfigInterface|array $config
     * @return void
     */
    public function setConfig($config)
    {
        if($config instanceof ConfigInterface) {
            $this->config = $config;
        } else {
            $this->config = new Config($config);
        }
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set if checking should abort on first failure.
     *
     * @param boolean $breakOnFailure
     */
    public function setBreakOnFailure($breakOnFailure)
    {
        $this->config->setBreakOnFailure((bool)$breakOnFailure);
    }

    /**
     * @return boolean
     */
    public function getBreakOnFailure()
    {
        return $this->config->getBreakOnFailure();
    }

    /**
     * Set severity of error that will result in a check failing. Defaults to:
     *  E_WARNING|E_PARSE|E_USER_ERROR|E_USER_WARNING|E_RECOVERABLE_ERROR
     *
     * @param int $catchErrorSeverity
     */
    public function setCatchErrorSeverity($catchErrorSeverity)
    {
        $this->config->setCatchErrorSeverity((int)$catchErrorSeverity);
    }

    /**
     * Get current severity of error that will result in a check failing.
     *
     * @return int
     */
    public function getCatchErrorSeverity()
    {
        return $this->config->getCatchErrorSeverity();
    }
}
