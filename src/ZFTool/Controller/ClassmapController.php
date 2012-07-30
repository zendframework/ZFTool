<?php

namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ArrayUtils;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\View\Model\ConsoleModel;
use Zend\File\ClassFileLocator;
use Zend\Loader\StandardAutoloader;
use Zend\Console\ColorInterface as Color;
use Zend\Version;


class ClassmapController extends AbstractActionController
{
    public function generateAction()
    {
        /* @var $request \Zend\Console\Request          */
        /* @var $console \Zend\Console\AdapterInterface */
        $request      = $this->getRequest();
        $console      = $this->getServiceLocator()->get('console');
        $relativePath = '';
        $usingStdout  = false;
        $directory    = $request->getParam('directory');
        $appending    = $request->getParam('append', false) || $request->getParam('a', false);
        $overwrite    = $request->getParam('overwrite', false) || $request->getParam('w', false);

        // Validate directory
        if (!is_dir($directory)) {
            $m = new ConsoleModel();
            $m->setErrorLevel(2);
            $m->setResult('Invalid library directory provided "' . $directory . '"' . PHP_EOL);
            return $m;
        }

        // Determine output file name
        $output = $request->getParam('destination', $directory . '/autoload_classmap.php');
        if ('-' == $output) {
            $output      = STDOUT;
            $usingStdout = true;
        } elseif (is_dir($output)) {
            $m = new ConsoleModel();
            $m->setErrorLevel(2);
            $m->setResult('Invalid output file provided' . PHP_EOL);
            return $m;
        } elseif (!is_writeable(dirname($output))) {
            $m = new ConsoleModel();
            $m->setErrorLevel(2);
            $m->setResult("Cannot write to '$output'; aborting." . PHP_EOL);
            return $m;
        } elseif (file_exists($output) && !$overwrite && !$appending) {
            $m = new ConsoleModel();
            $m->setErrorLevel(2);
            $m->setResult(
                "Autoload file already exists at '$output'," . PHP_EOL
                    . "but 'overwrite' or 'appending' flag was not specified; aborting." . PHP_EOL
            );
            return $m;
        } else {
            // We need to add the $libraryPath into the relative path that is created in the classmap file.
            $classmapPath = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname($output)));

            // Simple case: $libraryPathCompare is in $classmapPathCompare
            if (strpos($directory, $classmapPath) === 0) {
                $relativePath = substr($directory, strlen($classmapPath) + 1) . '/';
            } else {
                $libraryPathParts  = explode('/', $directory);
                $classmapPathParts = explode('/', $classmapPath);

                // Find the common part
                $count = count($classmapPathParts);
                for ($i = 0; $i < $count; $i++) {
                    if (!isset($libraryPathParts[$i]) || $libraryPathParts[$i] != $classmapPathParts[$i]) {
                        // Common part end
                        break;
                    }
                }

                // Add parent dirs for the subdirs of classmap
                $relativePath = str_repeat('../', $count - $i);

                // Add library subdirs
                $count = count($libraryPathParts);
                for (; $i < $count; $i++) {
                    $relativePath .= $libraryPathParts[$i] . '/';
                }
            }
        }

        if (!$usingStdout) {
            if ($appending) {
                $console->write('Appending to class file map ');
                $console->write($output, Color::LIGHT_WHITE);
                $console->write(' for library in ');
                $console->writeLine($directory, Color::LIGHT_WHITE);
            } else {
                $console->write('Creating classmap file for library in ');
                $console->writeLine($directory, Color::LIGHT_WHITE);
            }
            $console->write('Scanning for files containing PHP classes ');
        }


        // Get the ClassFileLocator, and pass it the library path
        $l = new ClassFileLocator($directory);

        // Iterate over each element in the path, and create a map of
        // classname => filename, where the filename is relative to the library path
        $map   = new \stdClass;
        $count = 0;
        foreach ($l as $file) {
            $filename = str_replace($directory . '/', '', str_replace(DIRECTORY_SEPARATOR, '/', $file->getPath()) . '/' . $file->getFilename());

            // Add in relative path to library
            $filename = $relativePath . $filename;

            foreach ($file->getClasses() as $class) {
                $map->{$class} = $filename;
            }

            $count++;
            $console->write('.');
        }

        if (!$usingStdout) {
            $console->writeLine(" DONE", Color::GREEN);
            $console->write('Found ');
            $console->write((int)$count, Color::LIGHT_WHITE);
            $console->writeLine(' PHP classes');
            $console->write('Creating classmap code ...');
        }

        // Check if we have found any PHP classes.
        if (!$count) {
            $console->writeLine('Cannot find any PHP classes in ' . $directory . '. Aborting!', Color::YELLOW);
            exit(1);
        }

        if ($appending) {
            $content = var_export((array)$map, true) . ';';

            // Prefix with __DIR__; modify the generated content
            $content = preg_replace("#(=> ')#", "=> __DIR__ . '/", $content);

            // Fix \' strings from injected DIRECTORY_SEPARATOR usage in iterator_apply op
            $content = str_replace("\\'", "'", $content);

            // Convert to an array and remove the first "array("
            $content = explode(PHP_EOL, $content);
            array_shift($content);

            // Load existing class map file and remove the closing "bracket ");" from it
            $existing = file($output, FILE_IGNORE_NEW_LINES);
            array_pop($existing);

            // Merge
            $content = implode(PHP_EOL, array_merge($existing, $content));
        } else {
            // Create a file with the class/file map.
            // Stupid syntax highlighters make separating < from PHP declaration necessary
            $content = '<' . "?php\n"
                . "// Generated by Zend Framework 2\n"
                . 'return ' . var_export((array)$map, true) . ';';

            // Prefix with __DIR__; modify the generated content
            $content = preg_replace("#(=> ')#", "=> __DIR__ . '/", $content);

            // Fix \' strings from injected DIRECTORY_SEPARATOR usage in iterator_apply op
            $content = str_replace("\\'", "'", $content);
        }

        // Remove unnecessary double-backslashes
        $content = str_replace('\\\\', '\\', $content);

        // Exchange "array (" width "array("
        $content = str_replace('array (', 'array(', $content);

        // Align "=>" operators to match coding standard
        preg_match_all('(\n\s+([^=]+)=>)', $content, $matches, PREG_SET_ORDER);
        $maxWidth = 0;

        foreach ($matches as $match) {
            $maxWidth = max($maxWidth, strlen($match[1]));
        }

        $content = preg_replace('(\n\s+([^=]+)=>)e', "'\n    \\1' . str_repeat(' ', " . $maxWidth . " - strlen('\\1')) . '=>'", $content);

        if (!$usingStdout) {
            $console->writeLine(" DONE" . PHP_EOL, Color::GREEN);
            $console->write('Writing classmap to ');
            $console->write($output, Color::LIGHT_WHITE);
            $console->write('... ');
        }

        // Write the contents to disk
        file_put_contents($output, $content);

        if (!$usingStdout) {
            $console->writeLine(" DONE", Color::GREEN);
            $console->writeLine('Wrote classmap to ' . realpath($output), Color::LIGHT_WHITE);
        }
    }


}
