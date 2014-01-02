<?php
/**
 * ZFTool test suite
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend
 */

/*
 * Set error reporting to the level to which Zend Framework code must comply.
 */
error_reporting( E_ALL | E_STRICT );

/*
 * Determine the root, library, and tests directories of the framework
 * distribution.
 */
$zfToolRoot   = dirname(__DIR__);
$zfToolTests  = "$zfToolRoot/tests";
$zfToolSrc    = "$zfToolRoot/src";
/*
 * Prepend the Zend Framework library/ and tests/ directories to the
 * include_path. This allows the tests to run out of the box and helps prevent
 * loading other copies of the framework code and tests that would supersede
 * this copy.
 */
$path = array(
    $zfToolTests,
    $zfToolSrc,
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));

// Set the user_agent for Github API
ini_set('user_agent', 'ZFTool - Zend Framework 2 command line tool');

/**
 * Setup autoloading
 */
include_once __DIR__ . '/../vendor/autoload.php';
