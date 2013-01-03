  Zend Framework 2 Tool
=========================

**ZFTool** is an utility module for maintaining modular Zend Framework 2 applications.

## Features
 * Class-map generator
 * Listing of loaded modules

## Requirements
 * Zend Framework 2.0.0 or later.
 * PHP 5.3.3 or later.
 * Console access to the application being maintained (shell, command prompt)

## Installation using [Composer](http://getcomposer.org)
 1. Open console (command prompt)
 2. Go to your application's directory.
 2. Run `composer require zendframework/zftool:dev-master`

## Manual installation
 1. Clone using `git` or [download zipball](https://github.com/zendframework/ZFTool/zipball/master).
 1. Extract to `vendor/ZFTool` in your ZF2 application
 1. Edit your `config/application.config.php` and add `ZFTool` to `modules` array.
 1. Open console and try one of the following commands...


## Usage

### Basic information

    zf.php modules [list]           show loaded modules
    zf.php version | --version      display current Zend Framework version

### Classmap generator

    zf.php classmap generate <directory> <classmap file> [--append|-a] [--overwrite|-w]

    <directory>         The directory to scan for PHP classes (use "." to use current directory)
    <classmap file>     File name for generated class map file  or - for standard output. If not supplied, defaults to
                        autoload_classmap.php inside <directory>.
    --append | -a       Append to classmap file if it exists
    --overwrite | -w    Whether or not to overwrite existing classmap file

### Compile the PHAR file

You can create a .phar file containing the ZFTool project. In order to compile ZFTool in a .phar file you need
to execute the following command:

    bin/create-phar.php

This command will create a zftool.phar file in th bin folder.
You can use and ship only this file to execute all the ZFTool functionalities.


## Todo
 * Module maintenance (installation, configuration, removal etc.)
 * Inspection of application configuration.
 * Deploying zf2 skeleton applications.
 * Reading and writing app configuration.

