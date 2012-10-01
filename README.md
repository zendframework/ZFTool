  Zend Framework 2 Tool
=========================

**ZFTool** is an utility module for maintaining modular Zend Framework 2 applications. It currently provides
the following functionality:

 * Module maintenance (installation, configuration, removal etc.)
 * Inspection of application configuration.
 * Deploying zf2 skeleton applications.

## Requirements

 * Zend Framework 2.0.0 RC1 or later.
 * PHP 5.3.3 or later.
 * Console access to the application being maintained (shell, command prompt)

## Installation
 
 1. Clone using `git` or [download zipball](https://github.com/zendframework/ZFTool/zipball/master).
 1. Extract to `vendor/ZFTool` in your ZF2 application
 1. Edit your `config/application.config.php` and add `ZFTool` to `modules` array.
 1. Open console and try one of the following commands...

## Usage

### Basic information

    zf.php modules [list]           show loaded modules
    zf.php version | --version      display current Zend Framework version

### Configuration

    zf.php config [list]                list all configuration options
    zf.php config get <name>            display a single config value, i.e. "config get db.host"
    zf.php config set <name> <value>    set a single config value (use only to change scalar values)

### Classmap generator

    zf.php classmap generate <directory> <classmap file> [--append|-a] [--overwrite|-w]

    <directory>         The directory to scan for PHP classes (use "." to use current directory)
    <classmap file>     File name for generated class map file  or - for standard output.If not supplied, defaults to autoload_classmap.php inside
                        <directory>.
    --append | -a       Append to classmap file if it exists
    --overwrite | -w    Whether or not to overwrite existing classmap file
