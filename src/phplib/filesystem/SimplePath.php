<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/22
 * Time: 上午11:35
 */

namespace phplib\filesystem;


class SimplePath
{
    protected $basePath;
    protected $pathName;
    protected $fileName;

    protected static function placeHolders($key = null)
    {
        $places = array(
            '{year}' => date('Y'),
            '{month}' => date('m'),
            '{day}' => date('d'),
            '{hour}' => date('H'),
            '{minute}' => date('i'),
            '{second}' => date('s'),
            '{week}' => date('W'),
            '{wday}' => date('w'),
            '{time}' => time(),
        );

        return empty($key) ? $places : $places[$key];
    }

    public function __construct($basePath = '', $baseUrl = '/')
    {
        $this->basePath = $basePath;
    }

    public function buildPathName($pathFormat, $prefix = null, $suffix = null)
    {
        $places = self::placeHolders();
        $pathName = str_replace(array_keys($places), array_values($places), trim($pathFormat, "/\\"));

        $prefix = rtrim($prefix, "/\\");
        if ($prefix)
            $pathName = $prefix . DIRECTORY_SEPARATOR . $pathName;

        $suffix = ltrim($suffix, "/\\");
        if ($suffix)
            $pathName .= DIRECTORY_SEPARATOR . $suffix;

        $this->pathName = $pathName;
        return $this;
    }

    public function createPath($mode = 0755, $recursive = true, $context = null)
    {
        $basePath = (stripos($this->pathName, '/') === 0) ? '' : (rtrim($this->basePath, '/') . DIRECTORY_SEPARATOR);
        $path = $basePath . $this->pathName;
        return $context ? mkdir($path, $mode, $recursive, $context) : mkdir($path, $mode, $recursive);
    }

    public function buildFileName($fileFormat, $extensionName = '', $includeDot = false)
    {
        $places = self::placeHolders();
        $fileName = str_replace(array_keys($places), array_values($places), $fileFormat);
        if ($extensionName)
            $fileName .= ($includeDot ? '' : '.') . $extensionName;

        $this->fileName = $fileName;
        return $this;
    }

    public function getFilePath()
    {
        $basePath = (stripos($this->pathName, '/') === 0) ? '' : (rtrim($this->basePath, '/') . DIRECTORY_SEPARATOR);
        return $basePath . $this->pathName . DIRECTORY_SEPARATOR . $this->fileName;
    }

    public function getFileUrl()
    {
        $pathUrl = ltrim(str_replace("\\", '/', $this->pathName), '/');
        return $pathUrl . '/' . $this->fileName;
    }
}