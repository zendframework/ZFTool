  ZF2 Tool Diagnostics
==========================

1. [Available checks](#available-checks)
2. [Running diagnostics in console](#running-diagnostics-in-console)
3. [Running diagnostics in web browser](#running-diagnostics-in-web-browser)
4. [What is a check?](#what-is-a-check)
5. [Adding checks to your module](#adding-checks-to-your-module)
6. [Checks in config files](#checks-in-config-files)
7. [Using built-in diagnostics checks](#using-built-in-diagnostics-checks)
8. [Providing debug information in checks](#providing-debug-information-in-checks)

## Available checks

 * All checks from [ZendDiagnostics](https://github.com/zendframework/ZendDiagnostics#zenddiagnostics)

## Running diagnostics in console

After installing ZF2 tool, you can run application diagnostics with the following console command:

    php public/index.php diag

This will run the default set of diag checks and in case of trouble, display any errors or warnings.
![Running diag in console](img/simple-run.png)

To display additional information on the checks performed, you can run diagnostics in verbose mode using
`--verbose` or `-v` switches:

    php public/index.php diag -v

![Verbose mode diagnostics](img/verbose-run.png)

Some checks will also produce debug information, which you can display with `--debug` switch:

    # Run diagnostics in "debug" mode
    php public/index.php diag --debug

You could also specify which checks you want to run (which module to check):

    # Run only checks included in Application module
    php public/index.php diag Application


## Running diagnostics in web browser

In order to enable diagnostics in browser, copy the included `config/zftool.global.php.dist` file to
your `config/autoload/` directory and rename it to `zftool.global.php`. Now open the file and uncomment
the `router => array()` section. The default URL for diagnostics is simply:

    http://yourwebsite/diagnostics

You can always change it to anything you like by editing the above config file.

![Browser-based diagnostics](img/browser-run.png)



## What is a check?

A check is simply:

 * Any function (anonymous, named, method), or
 * Any class implementing `ZFTool\Diagnostics\Check\CheckInterface`

A check returns:

  * `true` which means check passed OK,
  * `false` which means check failed,
  * a `string` with warning message,
  * or instance of `ZFTool\Diagnostics\Result`, including Success, Failure, Warning.


## Adding checks to your module

The simplest way to add checks is to write `getDiagnostics()` method in your module main class. For example, we could
add the following checks to our `modules/Application/Module.php` file:

````php
<?php
namespace Application;

class Module {
    // [...]

    /**
     * This method should return an array of checks,
     */
    public function getDiagnostics()
    {
        return array(
            'Memcache present' => function(){
                return function_exists('memcache_add');
            },
            'Cache directory exists' => function() {
                return file_exists('data/cache') && is_dir('data/cache');
            }
        );
    }
}
````

The returned array should contain pairs of a `label => check`. The label can be any string and will only be
used as a description of the tested requirement. The `check` can be a callable, a function or a string, which
will automatically be expanded. The following chapter describes all available methods of declaring checks.


## Checks in config files


The second method is to define checks in config files which will be lazy-loaded as needed. Diagnostic
component can understand the following types of definitions:

### Check function name

The simplest form of a check is a "callable", a function or a method. Here are a few examples:

````php
<?php
// modules/Application/config/module.config.php
return array(
    'diagnostics' => array(

        // "application" check group
        'application' => array(
            // invoke static method Application\Module::checkEnvironment()
            'Check environment' => array('Application\Module', 'checkEnvironment'),

            // invoke php built-in function with a parameter
            'Check if public dir exists' => array('file_exists', 'public/'),

            // invoke a static method with 2 parameters
            'Check paths' => array(
                array('Application\Module', 'checkPaths'),
                'foo/',
                'bar/'
            )
        )
    )
);
````

### Check class name

Assuming we have written following check class:

````php
<?php
namespace Application\Check;

class is64bit extends ZFTool\Diagnostics\Check\AbstractCheck
{
    public function run()
    {
        return PHP_INT_SIZE === 8;
    }
}
````

We can now use it in our Application configuration file in the following way:

````php
<?php
// modules/Application/config/module.config.php
return array(
    'diagnostics' => array(
        'application' => array(
            'Verify that this system is 64bit' => 'Application\Check\is64bit'
        )
    )
);
````

It is also possible to provide constructor parameters for check instances, like so:

````php
<?php
namespace Application\Check;

class EnvironmentTest extends ZFTool\Diagnostics\Check\AbstractCheck
{
    // [...]
}
````

````php
<?php
// modules/Application/config/module.config.php
return array(
    'diagnostics' => array(
        'application' => array(
            // equivalent of constructing new EnvironmentTest("production", 15);
            'Verify environment' => array(
                'Application\Check\is64bit',
                'production',
                15
            )
        )
    )
);
````

### Check instance fetched from Service Manager

If we define the check as a string, the diag component will attempt to fetch the check from
Application Service Manager. For example, we could have the following check class:

````php
<?php
namespace Application\Check;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZFTool\Diagnostics\Check\AbstractTest;

class CheckUserModule extends AbstractTest implements ServiceLocatorAwareInterface
{
    protected $sl;

    public function run()
    {
        return $this->getServiceLocator()->has('zfcuser');
    }

    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->sl = $sl;
    }

    public function getServiceLocator()
    {
        return $this->sl;
    }
}
````

Now we just have to add proper definition to Service Manager and then diagnostics.


````php
<?php
// modules/Application/config/module.config.php
return array(
    service_manager' => array(
        invokables' => array(
            'CheckUserModuleTest' => 'Application\check\CheckUserModule',
        ),
    ),

    'diagnostics' => array(
        'application' => array(
            'Check if user module is loaded' => 'CheckUserModuleTest'
        )
    )
);
````


## Using built-in diagnostics checks

ZFTool uses a bundle of general-purpose checks from
[ZendDiagnostics](https://github.com/zendframework/ZendDiagnostics#zenddiagnostics) but also provides ZF2-specific
classes.


## Providing debug information in checks

A check function or class can return an instance of `Success`, `Failure` or `Warning` providing detailed information
on the check performed and its result:

````php
     $success = new ZFTool\Diagnostics\Result\Success( $message, $debugData )
     $failure = new ZFTool\Diagnostics\Result\Failure( $message, $debugData )
     $warning = new ZFTool\Diagnostics\Result\Warning( $message, $debugData )
````

Below is an example of a module-defined checks that return various responses:

````php
<?php
// modules/Application/Module.php

namespace Application;

use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Warning;

class Module {
    // [...]

    public function getDiagnostics()
    {
        return array(
            'Check PHP extensions' => function(){
                if (!extension_loaded('mbstring')) {
                    return new Failure(
                        'MB string is required for this module to work',
                        get_loaded_extensions()
                    );
                }

                if (!extension_loaded('apc')) {
                    return new Warning(
                        'APC extension is not loaded. It is highly recommended for performance.',
                        get_loaded_extensions()
                    );
                }

                return new Success('Everything in order...');
            }
        );
    }
}
````

