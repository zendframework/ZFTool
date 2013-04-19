<?php
namespace ZFTool\Diagnostics\Reporter;

use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use ZFTool\Diagnostics\RunEvent;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use Zend\Stdlib\StringUtils;

class BasicConsole extends AbstractReporter
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

    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    public function onStart(RunEvent $e)
    {
        $this->stopped = false;
        $this->width = $this->console->getWidth();
        $this->total = count($e->getParam('tests'));

        // Calculate gutter width to accommodate number of tests passed
        if ($this->total <= $this->width) {
            $this->gutter = 0; // everything fits well
        } else {
            $this->countLength = floor(log10($this->total)) + 1;
            $this->gutter = ($this->countLength * 2) + 11;
        }

        $this->console->writeLine('Starting diagnostics:');
        $this->console->writeLine('');
    }

    public function onAfterRun(RunEvent $e)
    {
        $result = $e->getLastResult();

        // Draw a symbol
        if ($result instanceof Success) {
            $this->console->write('.', Color::GREEN);
        } elseif ($result instanceof Failure) {
            $this->console->write('F', Color::WHITE, Color::RED);
        } elseif ($result instanceof Warning) {
            $this->console->write('!', Color::YELLOW);
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

    public function onFinish(RunEvent $e)
    {
        /* @var $results \ZFTool\Diagnostics\Result\Collection */
        $results = $e->getResults();
        $this->console->writeLine();
        $this->console->writeLine();

        // Display a summary line
        if ($results->getFailureCount() == 0 && $results->getWarningCount() == 0 && $results->getUnknownCount() == 0) {
            $line = 'OK (' . $this->total . ' diagnostic tests)';
            $this->console->writeLine(
                str_pad($line, $this->width-1, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::GREEN
            );
        } elseif ($results->getFailureCount() == 0) {
            $line = $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->console->writeLine(
                str_pad($line, $this->width-1, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::YELLOW
            );
        } else {
            $line = $results->getFailureCount() . ' failures, ';
            $line .= $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->console->writeLine(
                str_pad($line, $this->width, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::RED
            );
        }

        $this->console->writeLine();

        // Display a list of failures and warnings
        foreach ($results as $test) {
            /* @var $test \ZFTool\Diagnostics\Test\TestInterface */
            /* @var $result \ZFTool\Diagnostics\Result\ResultInterface */
            $result = $results[$test];

            if ($result instanceof Failure) {
                $this->console->writeLine('Failure: ' . $test->getLabel(), Color::RED);
                $message = $result->getMessage();
                if ($message) {
                    $this->console->writeLine($message, Color::RED);
                }
                $this->console->writeLine();
            } elseif ($result instanceof Warning) {
                $this->console->writeLine('Warning: ' . $test->getLabel(), Color::YELLOW);
                $message = $result->getMessage();
                if ($message) {
                    $this->console->writeLine($message, Color::YELLOW);
                }
                $this->console->writeLine();
            } elseif (!$result instanceof Success) {
                $this->console->writeLine('Unknown result ' . get_class($result) . ': ' . $test->getLabel(), Color::YELLOW);
                $message = $result->getMessage();
                if ($message) {
                    $this->console->writeLine($message, Color::YELLOW);
                }
                $this->console->writeLine();
            }
        }

        // Display information that the test has been aborted.
        if ($this->stopped) {
            $this->console->writeLine('Diagnostics aborted because of a failure.', Color::RED);
        }
    }

    public function onStop(RunEvent $e)
    {
        $this->stopped = true;
    }

    /**
     * @param \Zend\Console\Adapter\AdapterInterface $console
     */
    public function setConsole($console)
    {
        $this->console = $console;

        // Update width
        $this->width = $console->getWidth();
    }

    /**
     * @return \Zend\Console\Adapter\AdapterInterface
     */
    public function getConsole()
    {
        return $this->console;
    }


}
