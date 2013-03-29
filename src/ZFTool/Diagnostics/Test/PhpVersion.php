<?php
namespace ZFTool\Diagnostics\Test;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;

/**
 * Validate PHP version
 *
 * @package ZFTool\Diagnostics\Test
 */
class PhpVersion extends AbstractTest implements TestInterface {

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @param string $expectedVersion   The expected version
     * @param string $operator  One of: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne
     * @throws \ZFTool\Diagnostics\Exception\InvalidArgumentException
     */
    public function __construct($expectedVersion, $operator = '>=')
    {
        if (!is_scalar($expectedVersion)) {
            throw new InvalidArgumentException(
                'Expected version as a string, got '.gettype($expectedVersion)
            );
        }

        $this->version = (string)$expectedVersion;

        if (!is_scalar($operator)) {
            throw new InvalidArgumentException(
                'Expected comparison operator as a string, got '.gettype($operator)
            );
        } 
        
        if(!in_array($operator, array(
            '<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'
        ))) {
            throw new InvalidArgumentException(
                'Unknown comparison operator '.$operator
            );
        }

        $this->operator = $operator;
   }


    public function run()
    {
        if (version_compare(PHP_VERSION, $this->version, $this->operator)) {
            return new Success('Current PHP version is ' . PHP_VERSION, PHP_VERSION);
        } else {
            return new Failure('Current PHP version ' . PHP_VERSION, PHP_VERSION);
        }
    }
}