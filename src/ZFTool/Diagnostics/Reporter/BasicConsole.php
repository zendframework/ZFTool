<?php
namespace ZFTool\Diagnostics\Reporter;

use ArrayObject;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\Collection as ResultsCollection;
use ZendDiagnostics\Result\FailureInterface as Failure;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\SkipInterface as Skip;
use ZendDiagnostics\Result\SuccessInterface as Success;
use ZendDiagnostics\Result\WarningInterface as Warning;
use ZendDiagnostics\Runner\Reporter\ReporterInterface;

class BasicConsole implements ReporterInterface
{
    /**
     * @var \Zend\Console\Adapter\AdapterInterface
     */
    protected $console;

    protected $width = 80;
    protected $total = 0;
    protected $iter = 1;
    protected $pos = 1;
    protected $countLength;
    protected $gutter;
    protected $stopped = false;

    /**
     * Create instance of reporter.
     *
     * @param Console $console
     */
    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    /**
     * This method is called right after Reporter starts running, via Runner::run()
     *
     * @param  ArrayObject $checks
     * @param  array       $runnerConfig
     * @return void
     */
    public function onStart(ArrayObject $checks, $runnerConfig)
    {
        $this->stopped = false;
        $this->width = $this->console->getWidth();
        $this->total = $checks->count();

        // Calculate gutter width to accommodate number of checks passed
        if ($this->total <= $this->width) {
            $this->gutter = 0; // everything fits well
        } else {
            $this->countLength = floor(log10($this->total)) + 1;
            $this->gutter = ($this->countLength * 2) + 11;
        }

        $this->console->writeLine('Starting diagnostics:');
        $this->console->writeLine('');
    }

    /**
     * This method is called before each individual Check is performed. If this
     * method returns false, the Check will not be performed (will be skipped).
     *
     * @param  CheckInterface $check Check instance that is about to be performed.
     * @param  bool           $alias The alias being targeted by the check
     * @return bool|void      Return false to prevent check from happening
     */
    public function onBeforeRun(CheckInterface $check, $alias = null) {}

    /**
     * This method is called every time a Check has been performed.
     *
     * @param  CheckInterface  $check  A Check instance that has just finished running
     * @param  ResultInterface $result Result for that particular check instance
     * @param  bool            $alias  The alias being targeted by the check
     * @return bool|void       Return false to prevent from running additional Checks
     */
    public function onAfterRun(CheckInterface $check, ResultInterface $result, $alias = null)
    {
        // Draw a symbol
        if ($result instanceof Success) {
            $this->console->write('.', Color::GREEN);
        } elseif ($result instanceof Failure) {
            $this->console->write('F', Color::WHITE, Color::RED);
        } elseif ($result instanceof Warning) {
            $this->console->write('!', Color::YELLOW);
        } elseif ($result instanceof Skip) {
            $this->console->write('S', Color::YELLOW);
        } else {
            $this->console->write('?', Color::YELLOW);
        }

        $this->pos++;

        // Check if we need to move to the next line
        if ($this->gutter > 0 && $this->pos > $this->width - $this->gutter) {
            $this->console->write(
                str_pad(
                    str_pad($this->iter, $this->countLength, ' ', STR_PAD_LEFT) . ' / ' . $this->total .
                    ' (' . str_pad(round($this->iter / $this->total * 100), 3, ' ', STR_PAD_LEFT) . '%)'
                    , $this->gutter, ' ', STR_PAD_LEFT
                )
            );
            $this->pos = 1;
        }

        $this->iter++;
    }

    /**
     * This method is called when Runner has been aborted and could not finish the
     * whole run().
     *
     * @param  ResultsCollection $results Collection of Results for performed Checks.
     * @return void
     */
    public function onStop(ResultsCollection $results)
    {
        $this->stopped = true;
    }

    /**
     * This method is called when Runner has finished its run.
     *
     * @param  ResultsCollection $results Collection of Results for performed Checks.
     * @return void
     */
    public function onFinish(ResultsCollection $results)
    {
        /* @var $results \ZendDiagnostics\Result\Collection */
        $this->console->writeLine();
        $this->console->writeLine();

        // Display a summary line
        if (
            $results->getFailureCount() == 0 &&
            $results->getWarningCount() == 0 &&
            $results->getUnknownCount() == 0 &&
            $results->getSkipCount() == 0
        ) {
            $line = 'OK (' . $this->total . ' diagnostic checks)';
            $this->console->writeLine(
                str_pad($line, $this->width-1, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::GREEN
            );
        } elseif ($results->getFailureCount() == 0) {
            $line = $results->getWarningCount() . ' warnings';

            if ($results->getSkipCount() > 0) {
                $line .= ', ' . $results->getSkipCount() . ' skipped checks';
            }

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown check results';
            }

            $line .= ', ' . $results->getSuccessCount() . ' successful checks';

            $line .= '.';

            $this->console->writeLine(
                str_pad($line, $this->width-1, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::YELLOW
            );
        } else {
            $line = $results->getFailureCount() . ' failures, ';
            $line .= $results->getWarningCount() . ' warnings';

            if ($results->getSkipCount() > 0) {
                $line .= ', ' . $results->getSkipCount() . ' skipped checks';
            }

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown check results';
            }

            $line .= ', ' . $results->getSuccessCount() . ' successful checks';

            $line .= '.';

            $this->console->writeLine(
                str_pad($line, $this->width, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::RED
            );
        }

        $this->console->writeLine();

        // Display a list of failures and warnings
        foreach ($results as $check) {
            /* @var $check CheckInterface */
            /* @var $result ResultInterface */
            $result = $results[$check];

            if ($result instanceof Failure) {
                $this->console->writeLine('Failure: ' . $check->getLabel(), Color::RED);
                $message = $result->getMessage();
                if ($message) {
                    $this->console->writeLine($message, Color::RED);
                }
                $this->console->writeLine();
            } elseif ($result instanceof Warning) {
                $this->console->writeLine('Warning: ' . $check->getLabel(), Color::YELLOW);
                $message = $result->getMessage();
                if ($message) {
                    $this->console->writeLine($message, Color::YELLOW);
                }
                $this->console->writeLine();
            } elseif ($result instanceof Skip) {
                $this->console->writeLine('Skipped: ' . $check->getLabel(), Color::YELLOW);
                $message = $result->getMessage();
                if ($message) {
                    $this->console->writeLine($message, Color::YELLOW);
                }
                $this->console->writeLine();
            } elseif (!$result instanceof Success) {
                $this->console->writeLine('Unknown result ' . get_class($result) . ': ' . $check->getLabel(), Color::YELLOW);
                $message = $result->getMessage();
                if ($message) {
                    $this->console->writeLine($message, Color::YELLOW);
                }
                $this->console->writeLine();
            }
        }

        // Display information that the check has been aborted.
        if ($this->stopped) {
            $this->console->writeLine('Diagnostics aborted because of a failure.', Color::RED);
        }
    }

    /**
     * Set Console adapter to use.
     *
     * @param Console $console
     */
    public function setConsole($console)
    {
        $this->console = $console;

        // Update width
        $this->width = $console->getWidth();
    }

    /**
     * Get currently used Console adapter
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }
}
