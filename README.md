  Zend Framework 2 Tool
=========================

**ZFTool** is an utility module for maintaining modular Zend Framework 2 applications.
It runs from the command line and can be installed as ZF2 module or as PHAR (see below).

## Features
 * Class-map generator
 * Listing of loaded modules
 * Create a new project (install the ZF2 skeleton application)
 * Create a new module
 * Create a new controller
 * Create a new action in a controller
 * [Application diagnostics](docs/DIAGNOSTICS.md)

## Requirements
 * Zend Framework 2.0.0 or later.
 * PHP 5.3.3 or later.
 * Console access to the application being maintained (shell, command prompt)

## Installation using [Composer](http://getcomposer.org)
 1. Open console (command prompt)
 2. Go to your application's directory.
 3. Run `composer require zendframework/zftool:dev-master`
 4. Execute the `vendor/bin/zf.php` as reported below

## Using the PHAR file (zftool.phar)

 1. Download the [zftool.phar from packages.zendframework.com](http://packages.zendframework.com/zftool.phar)
 2. Execute the `zftool.phar` with one of the options reported below (`zftool.phar` replace the `zf.php`)

You can also generate the zftool.phar using the `bin/create-phar` command as reported below

## Usage

### Basic information

    zf.php modules [list]           show loaded modules
    zf.php version | --version      display current Zend Framework version

### Diagnostics

    zf.php diag [options] [module name]

    [module name]       (Optional) name of module to test
    -v --verbose        Display detailed information.
    -b --break          Stop testing on first failure.
    -q --quiet          Do not display any output unless an error occurs.
    --debug             Display raw debug info from tests.

### Project creation

    zf.php create project <path>

    <path>              The path of the project to be created

### Module creation

    zf.php create module <name> [<path>]

    <name>              The name of the module to be created
    <path>              The path to the root folder of the ZF2 application (optional)

### Controller creation:
	zf.php create controller <name> <module> [<path>]

	<name>      The name of the controller to be created
	<module>    The module in which the controller should be created
	<path>      The root path of a ZF2 application where to create the controller

### Action creation:
	zf.php create action <name> <controller> <module> [<path>]

	<name>          The name of the action to be created
	<controller>    The name of the controller in which the action should be created
	<module>        The module containing the controller
	<path>          The root path of a ZF2 application where to create the action

### Application configuration

    zf.php config list                  list all configuration option
    zf.php config get <name>            display a single config value, i.e. "config get db.host"
    zf.php config set <name> <value>    set a single config value (use only to change scalar values)

### Classmap generator

    zf.php classmap generate <directory> <classmap file> [--append|-a] [--overwrite|-w]

    <directory>         The directory to scan for PHP classes (use "." to use current directory)
    <classmap file>     File name for generated class map file  or - for standard output. If not supplied, defaults to
                        autoload_classmap.php inside <directory>.
    --append | -a       Append to classmap file if it exists
    --overwrite | -w    Whether or not to overwrite existing classmap file

### ZF library installation

    zf.php install zf <path> [<version>]

    <path>              The directory where to install the ZF2 library
    <version>           The version to install, if not specified uses the last available

### Compile the PHAR file

You can create a .phar file containing the ZFTool project. In order to compile ZFTool in a .phar file you need
to execute the following command:

    bin/create-phar

This command will create a *zftool.phar* file in the bin folder.
You can use and ship only this file to execute all the ZFTool functionalities.
After the *zftool.phar* creation, we suggest to add the folder bin of ZFTool in your PATH environment. In this
way you can execute the *zftool.phar* script wherever you are, for instance executing the command:

    mv zftool.phar /usr/local/bin/zftool.phar

Note: If the above fails due to permissions, run the mv line again with sudo.


## Todo
 * Module maintenance (installation, configuration, removal etc.) [installation DONE]
 * Inspection of application configuration. [DONE]
 * Deploying zf2 skeleton applications. [DONE]
 * Reading and writing app configuration. [DONE]
