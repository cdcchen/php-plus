<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/22
 * Time: 下午11:26
 */

namespace yiiext\storage\upyun;


use phplib\net\CUrl;

class UpYunClient
{
    const VERSION = '1.0';

    const ENDPOINT_AUTO       = 'v0.api.upyun.com';
    const ENDPOINT_TELECOM    = 'v1.api.upyun.com';
    const ENDPOINT_UNICOM     = 'v2.api.upyun.com';
    const ENDPOINT_CMCC       = 'v3.api.upyun.com';

    const CONTENT_TYPE       = 'Content-Type';
    const CONTENT_MD5        = 'Content-MD5';
    const CONTENT_SECRET     = 'Content-Secret';

    // 缩略图
    const X_GMKERL_THUMBNAIL = 'x-gmkerl-thumbnail';
    const X_GMKERL_TYPE      = 'x-gmkerl-type';
    const X_GMKERL_VALUE     = 'x-gmkerl-value';
    const X_GMKERL_QUALITY   = 'x­gmkerl-quality';
    const X_GMKERL_UNSHARP   = 'x­gmkerl-unsharp';
    /*}}}*/

    private $_scheme = 'http';
    private $_bucketName;
    private $_username;
    private $_password;
    private $_timeout = 30;

    protected $endpoint;

    /**
     * @var null|CUrl
     */
    protected $_curl = null;

    public function __construct($bucket_name, $username, $password, $endpoint = null, $timeout = 30)
    {
        $this->_bucketName = $bucket_name;
        $this->_username = $username;
        $this->_password = md5($password);
        $this->_timeout = $timeout;

        $this->_endpoint = is_null($endpoint) ? self::ENDPOINT_AUTO : $endpoint;
    }

    public function getCurl()
    {
        if ($this->_curl === null) {
            $options = [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_AUTOREFERER => false,
            ];
            $this->_curl = new CUrl($options);
        }

        return $this->_curl;
    }

    public function writeFile($filePath, $body, $options = [], $mkdir = true)
    {
        if (is_resource($body)) {
            $length = (int)fstat($body)['size'];
        }
        else {
            $body = is_array($body) ? http_build_query($body) : $body;
            $length = strlen($body);
        }

        $headers = $this->buildHeaders('PUT', $filePath, $length);
        $headers['mkdir'] = (bool)$mkdir;

        $url = $this->buildRequestUrl($filePath);
        $this->getCurl()->returnHeaders(true)
            ->setHttpHeaders(array_merge($headers, $options))->put($url, $body);

        $this->checkHttpStatusCode();

        if ($this->getCurl()->getErrno() === 0) {
            $info = $this->getCurl()->getResponseHeaders();
            var_dump($this->getCurl()->getBody());
            return [
                'width' => (int)$info['x-upyun-width'],
                'height' => (int)$info['x-upyun-height'],
                'type' => $info['x-upyun-file-type'],
                'frames' => (int)$info['x-upyun-frames'],
            ];
        }
        else
            return false;
    }

    public function readFile($file)
    {
        $url = $this->buildRequestUrl($file);
        $headers = $this->buildHeaders('GET', $file);
        $this->getCurl()
            ->setHttpHeaders($headers)->get($url);

        $this->checkHttpStatusCode();

        return $this->getCurl()->getErrno() === 0 ? $this->getCurl()->getRawData() : false;
    }

    public function deleteFile($file)
    {
        return $this->deleteDir($file);
    }

    public function fileInfo($file)
    {
        $url = $this->buildRequestUrl($file);
        $headers = $this->buildHeaders('HEAD', $file);
        $this->getCurl()->returnHeaders()
            ->setOption(CURLOPT_NOBODY, true)
            ->setHttpHeaders($headers)->head($url);

        $this->checkHttpStatusCode();

        if ($this->getCurl()->getErrno() === 0) {
            $info = $this->getCurl()->getResponseHeaders();
            return [
                'type' => $info['x-upyun-file-type'],
                'size' => (int)$info['x-upyun-file-size'],
                'date' => (int)$info['x-upyun-file-date'],
            ];
        }
        else
            return false;
    }

    public function createDir($path, $mkdir = true)
    {
        $url = $this->buildRequestUrl($path);
        $headers = $this->buildHeaders('POST', $path);
        $headers['folder'] = true;
        $headers['mkdir'] = (bool)$mkdir;
        $this->getCurl()
            ->setHttpHeaders($headers)->post($url);

        $this->checkHttpStatusCode();

        return $this->getCurl()->getErrno() === 0 && $this->getCurl()->getHttpCode() === 200;
    }

    public function deleteDir($path)
    {
        $url = $this->buildRequestUrl($path);
        $headers = $this->buildHeaders('DELETE', $path);
        $this->getCurl()
            ->setHttpHeaders($headers)->delete($url);

        $this->checkHttpStatusCode();

        return $this->getCurl()->getErrno() === 0 && $this->getCurl()->getHttpCode() === 200;
    }

    public function readDir($path)
    {
        $url = $this->buildRequestUrl($path);
        $headers = $this->buildHeaders('GET', $path);
        $this->getCurl()
            ->setHttpHeaders($headers)->get($url);

        $this->checkHttpStatusCode();

        if ($this->getCurl()->getErrno() === 0) {
            $body = $this->getCurl()->getRawData();
            $lines = explode("\n", $body);
            $files = [];
            foreach ($lines as $line) {
                list($name, $type, $size, $date) = explode("\t", $line);
                $files[] = [
                    'name' => $name,
                    'type' => $type,
                    'size' => (int)$size,
                    'date' => (int)$date,
                ];
            }
            return $files;
        }
        else
            return false;
    }

    public function getBucketUsage()
    {
        $path = '/?usage';
        $url = $this->buildRequestUrl($path);
        $headers = $this->buildHeaders('GET', $path);
        $this->getCurl()
            ->setHttpHeaders($headers)->get($url);

        $this->checkHttpStatusCode();

        if ($this->getCurl()->getErrno() === 0)
            return $this->getCurl()->getRawData();
        else
            return false;
    }

    public function version()
    {
        return self::VERSION;
    }

    private function checkHttpStatusCode()
    {
        $code = $this->getCurl()->getHttpCode();

        if ($code === 200) return true;

        var_dump($code);exit;
        throw new \Exception('request error', $code);

    }

    private function buildRequestUrl($path)
    {
        $path = $this->buildRequestPath($path);
        return  "{$this->_scheme}://{$this->_endpoint}{$path}";
    }

    private function buildRequestPath($path)
    {
        return '/' . $this->_bucketName . '/' . ltrim($path, '/');
    }

    private function buildHeaders($method, $path, $length = 0)
    {
        $uri = $this->buildRequestPath($path);
        $method = strtoupper($method);
        $date = $this->getDate();
        $length = (int)$length;
        $sign = $this->generateSignature($method, $uri, $date, $length);

        return [
            'Expect: ',
            "Authorization: {$sign}",
            "Date: {$date}",
            "Content-Length: {$length}",
        ];
    }

    private function getDate()
    {
        return gmdate('D, d M Y H:i:s \G\M\T');
    }

    private function generateSignature($method, $uri, $date, $length)
    {
        $method = strtoupper($method);
        $sign = "{$method}&{$uri}&{$date}&{$length}&{$this->_password}";

        return 'UpYun ' . $this->_username . ':' . md5($sign);
    }
}