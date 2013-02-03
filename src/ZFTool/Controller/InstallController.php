<?php

namespace ZFTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\ConsoleModel;
use ZFTool\Model\Zf;
use ZFTool\Model\Utility;
use Zend\Console\ColorInterface as Color;

class InstallController extends AbstractActionController
{

    public function zfAction()
    {
        if (!extension_loaded('zip')) {
            return $this->sendError('You need to install the ZIP extension of PHP');
        }
        $console = $this->getServiceLocator()->get('console');
        $tmpDir  = sys_get_temp_dir();
        $request = $this->getRequest();
        $version = $request->getParam('version');
        $path    = rtrim($request->getParam('path'), '/');

        if (file_exists($path)) {
            return $this->sendError (
                "The directory $path already exists. You cannot install the ZF2 library here."
            );
        }

        if (empty($version)) {
            $version = Zf::getLastVersion();
            if (false === $version) {
                return $this->sendError (
                    "I cannot connect to the Zend Framework website."
                );
            }
        } else {
            if (!Zf::checkVersion($version)) {
                return $this->sendError (
                    "The specified ZF version, $version, doesn't exist."
                );
            }
        }

        $tmpFile = ZF::getTmpFileName($tmpDir, $version);
        if (!file_exists($tmpFile)) {
            if (!Zf::downloadZip($tmpFile, $version)) {
                return $this->sendError (
                    "I cannot download the ZF2 library from github."
                );
            }
        }

        $zip = new \ZipArchive;
        if ($zip->open($tmpFile)) {
            $zipFolders = $zip->statIndex(0);
            $zipFolder = $tmpDir . '/' . rtrim($zipFolders['name'], "/");
            if (!$zip->extractTo($tmpDir)) {
                return $this->sendError("Error during the unzip of $tmpFile.");
            }

            $result = Utility::copyFiles($zipFolder, $path);
            if (file_exists($zipFolder)) {
                Utility::deleteFolder($zipFolder);
            }
            $zip->close();
            if (false === $result) {
                return $this->sendError("Error during the copy of the files in $path.");
            }
        }

        $console->writeLine("The ZF library $version has been installed in $path.", Color::GREEN);
    }

    /**
     * Send an error message to the console
     *
     * @param  string $msg
     * @return ConsoleModel
     */
    protected function sendError($msg)
    {
        $m = new ConsoleModel();
        $m->setErrorLevel(2);
        $m->setResult($msg . PHP_EOL);
        return $m;
    }
}
