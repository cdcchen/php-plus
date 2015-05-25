<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 14/12/20
 * Time: 下午11:53
 */

namespace yiiext\storage\upyun;

use yii\base\InvalidConfigException;
use yiiext\storage\Storage;

class UpYunStorage extends Storage
{
    /**
     * @var null|string
     */
    public $endpoint = null;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $bucket;

    /**
     * @var bool
     */
    public $autoMkDir = true;

    /**
     * @var bool
     */
    public $isImageBucket = true;

    /**
     * @var int
     */
    public $timeout = 60;

    /**
     * @var null|array
     */
    protected $_options;

    /**
     * 又拍云api接口地址
     * @var UpYunClient
     */
    protected $_handle;

    public function init()
    {
        parent::init();

        if (empty($this->bucket))
            throw new InvalidConfigException('bucket is required');

        $this->_handle = new UpYunClient($this->bucket, $this->username, $this->password, $this->endpoint, $this->timeout);
    }

    public function setOption($option, $value = null)
    {
        if (is_array($option))
            $this->_options = array_merge($this->_options, $option);
        else
            $this->_options[$option] = $value;

        return $this;
    }

    public function resetOption()
    {
        $this->_options = null;
        return $this;
    }

    public function getHandle()
    {
        return $this->_handle;
    }

    protected function writeFile($filePath, $fileName)
    {
        return $this->putFile($filePath, $fileName, $this->_options);
    }

    protected function deleteFile($fileUrl)
    {
        $filePath = parse_url($fileUrl, PHP_URL_PATH);
        return $filePath ? $this->_handle->deleteFile($filePath) : false;
    }

    protected function exifFile($file)
    {

    }

    public function readDir($path)
    {
        return $this->_handle->readDir($path);
    }

    protected function putFile($filePath, $fileName, $opts = null)
    {
        if (empty($fileName))
            throw new \InvalidArgumentException('fileName is required.');

        if (empty($filePath))
            throw new \InvalidArgumentException('write filePath is required.');

        if (@file_exists($fileName) && @is_file($fileName) && @is_readable($fileName))
            $fileName = fopen($fileName, 'rb');

//        try {
            $result =  $this->_handle->writeFile($filePath, $fileName, $this->autoMkDir, $opts);
            if (is_array($result)) {
                $image['width'] = (int)$result['x-upyun-width'];
                $image['height'] = (int)$result['x-upyun-height'];
                $image['type'] = $result['x-upyun-file-type'];
                $image['frames'] = $result['x-upyun-frames'];
                return $image;
            }
            else
                return $result;
//        }
//        catch (\Exception $e) {
//            throw new $e->getMessage();
//            return false;
//        }
    }
}