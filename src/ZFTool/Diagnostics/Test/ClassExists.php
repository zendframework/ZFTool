<?php
namespace ZFTool\Diagnostics\Test;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;

/**
 * Validate that a class or a collection of classes is available.
 *
 * @package ZFTool\Diagnostics\Test
 */
class ClassExists extends AbstractTest implements TestInterface
{
    /**
     * @var array|\Traversable
     */
    protected $classes;

    protected $autoload = true;

    /**
     * @param string|array|\Traversable $classNames      Class name or an array of classes
     * @param bool                      $autoload        Use autoloader when looking for classes? (defaults to true)
     * @throws \ZFTool\Diagnostics\Exception\InvalidArgumentException
     */
    public function __construct($classNames, $autoload = true)
    {
        if (is_object($classNames) && !$classNames instanceof \Traversable) {
            throw new InvalidArgumentException(
                'Expected a class name (string), an array or Traversable of strings, got ' . get_class($classNames)
            );
        }

        if (!is_object($classNames) && !is_array($classNames) && !is_string($classNames)) {
            throw new InvalidArgumentException('Expected a class name (string) or an array of strings');
        }

        if (is_string($classNames)) {
            $this->classes = array($classNames);
        } else {
            $this->classes = $classNames;
        }

        $this->autoload = $autoload;
    }


    public function run()
    {
        foreach ($this->classes as $class) {
            if(!class_exists($class, $this->autoload)) {
                return new Failure('Class '.$class.' does not exist');
            }
        }
        return new Success();
    }
}
