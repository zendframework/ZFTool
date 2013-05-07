<?php
namespace ZFTool\Diagnostics\Test;

use ZFTool\Diagnostics\Exception\InvalidArgumentException;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;

/**
 * Validate that a given path (or a collection of paths) is a dir and is readable
 *
 * @package ZFTool\Diagnostics\Test
 */
class DirReadable extends AbstractTest implements TestInterface
{

    /**
     * @var array|\Traversable
     */
    protected $dir;

    /**
     * @param string|array|\Traversable $path    Path name or an array of paths
     * @throws \ZFTool\Diagnostics\Exception\InvalidArgumentException
     */
    public function __construct($path)
    {
        if (is_object($path) && !$path instanceof \Traversable) {
            throw new InvalidArgumentException(
                'Expected a dir name (string), an array or Traversable of strings, got ' . get_class($path)
            );
        }

        if (!is_object($path) && !is_array($path) && !is_string($path)) {
            throw new InvalidArgumentException('Expected a dir name (string) or an array of strings');
        }

        if (is_string($path)) {
            $this->dir = array($path);
        } else {
            $this->dir = $path;
        }
    }


    public function run()
    {
        foreach ($this->dir as $dir) {
            if (!is_dir($dir)) {
                return new Failure('"'.$dir . '" is not a directory.');
            }

            if (!is_readable($dir)) {
                return new Failure('"' . $dir . '" directory is not readable.');
            }
        }

        return new Success('',$this->dir);
    }
}
