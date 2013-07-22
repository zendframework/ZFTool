<?php

namespace ZFTool\Model;

use Zend\Config\Writer\PhpArray;
use Zend\Stdlib\ArrayUtils;

class Config
{
    protected $configPath;

    protected $config = array();

    public static function findValueInArray($dottedName, array $valueArray)
    {
        $value = $valueArray;

        $parts = explode('.', $dottedName);
        while ($curPart = array_shift($parts)) {
            if (isset($value[$curPart])) {
                $value = $value[$curPart];
            } else {
                $value = null;
            }
        }
        return $value;
    }

    public function __construct($configPath)
    {
        $this->configPath = $configPath;
        if (file_exists($configPath)) {
            $this->config = include($configPath);
        }
    }

    public function write($dottedName, $value)
    {

        $getset = function($getset, $name, $value, &$array) {
            $n = array_shift($name);
            if (count($name) > 0) {
                $array[$n] = array();
                $newArray = &$array[$n];
                $getset($getset, $name, $value, $newArray);
            } else {
                $array[$n] = $value;
            }
        };

        $newNestedArray = array();
        $getset($getset, explode('.', $dottedName), $value, $newNestedArray);

        $newFullConfig = ArrayUtils::merge($this->config, $newNestedArray);

        $phpArray = new PhpArray();
        $phpArray->toFile($this->configPath, $newFullConfig);
    }

    public function read($dottedName)
    {
        return static::findValueInArray($dottedName, $this->config);
    }

}
