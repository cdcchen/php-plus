<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 16/3/13
 * Time: 12:29
 */

namespace phpplus\filesystem;


class FileHelper
{

    /**
     * @var string the path (or alias) of a PHP file containing MIME type information.
     */
    public static $mimeMagicFile = __DIR__ . DIRECTORY_SEPARATOR . 'mimeTypes.php';

    private static $_mimeTypes = [];

    /**
     * Determines the MIME type of the specified file.
     * This method will first try to determine the MIME type based on
     * [finfo_open](http://php.net/manual/en/function.finfo-open.php). If the `fileinfo` extension is not installed,
     * it will fall back to [[getMimeTypeByExtension()]] when `$checkExtension` is true.
     * @param string $file the file name.
     * @param string $magicFile name of the optional magic database file (or alias), usually something like `/path/to/magic.mime`.
     * This will be passed as the second parameter to [finfo_open()](http://php.net/manual/en/function.finfo-open.php)
     * when the `fileinfo` extension is installed. If the MIME type is being determined based via [[getMimeTypeByExtension()]]
     * and this is null, it will use the file specified by [[mimeMagicFile]].
     * @param boolean $checkExtension whether to use the file extension to determine the MIME type in case
     * `finfo_open()` cannot determine it.
     * @return string the MIME type (e.g. `text/plain`). Null is returned if the MIME type cannot be determined.
     * @throws \Exception when the `fileinfo` PHP extension is not installed and `$checkExtension` is `false`.
     */
    public static function getMimeType($file, $magicFile = null, $checkExtension = true)
    {
        if (!extension_loaded('fileinfo')) {
            if ($checkExtension) {
                return static::getMimeTypeByExtension($file, $magicFile);
            }
            else {
                throw new \Exception('The fileinfo PHP extension is not installed.');
            }
        }

        $info = finfo_open(FILEINFO_MIME_TYPE, $magicFile);
        if ($info) {
            $result = finfo_file($info, $file);
            finfo_close($info);

            if ($result !== false) return $result;
        }

        return $checkExtension ? static::getMimeTypeByExtension($file, $magicFile) : null;
    }

    /**
     * Determines the MIME type based on the extension name of the specified file.
     * This method will use a local map between extension names and MIME types.
     * @param string $file the file name.
     * @param string $magicFile the path (or alias) of the file that contains all available MIME type information.
     * If this is not set, the file specified by [[mimeMagicFile]] will be used.
     * @return string the MIME type. Null is returned if the MIME type cannot be determined.
     */
    public static function getMimeTypeByExtension($file, $magicFile = null)
    {
        $mimeTypes = static::loadMimeTypes($magicFile);

        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if (isset($mimeTypes[$ext])) return $mimeTypes[$ext];
        }

        return null;
    }

    /**
     * Loads MIME types from the specified file.
     * @param string $magicFile the path (or alias) of the file that contains all available MIME type information.
     * If this is not set, the file specified by [[mimeMagicFile]] will be used.
     * @return array the mapping from file extensions to MIME types
     */
    protected static function loadMimeTypes($magicFile)
    {
        if ($magicFile === null)
            $magicFile = static::$mimeMagicFile;

        if (!isset(self::$_mimeTypes[$magicFile]))
            self::$_mimeTypes[$magicFile] = require($magicFile);

        return self::$_mimeTypes[$magicFile];
    }
}