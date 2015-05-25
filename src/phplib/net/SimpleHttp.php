<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/6
 * Time: 下午4:01
 */

namespace phplib\net;


class SimpleHttp extends SimpleWrapper
{
    protected $_headers = [];

    public function get($url)
    {
        $this->setMethod('GET');
        return $this->request($url);
    }

    public function post($url, $data = '')
    {
        $this->setMethod('POST')->setPostData($data);
        return $this->request($url);
    }

    public function setMethod($value)
    {
        $this->setOption('http', 'method', strtoupper($value));
        return $this;
    }

    public function addHeader(array $data)
    {
        $this->_headers = array_merge($this->_headers, $data);
        $this->setHeader();
        return $this;
    }

    protected function setHeader()
    {
        $headers = '';
        foreach ($this->_headers as $name => $value) {
            $value = trim($value);
            if (is_int($name))
                $headers .= trim($value) . "\r\n";
            else
                $headers .= "{$name}: {$value}\r\n";
        }

        $this->setOption('http', 'header', $headers);
        return $this;
    }

    public function setUserAgent($value)
    {
        $this->setOption('http', 'user_agent', $value);
        return $this;
    }

    public function setProxy($value)
    {
        $this->setOption('http', 'proxy', $value);
        return $this;
    }

    public function useFullURI($value = false)
    {
        $this->setOption('http', 'request_fulluri', (bool)$value);
        return $this;
    }

    public function followLocation($value = 1, $maxRedirects = 10)
    {
        $this->setOption('http', 'follow_location', (int)$value);
        $this->setOption('http', 'max_redirects', (int)$maxRedirects);
        return $this;
    }

    public function setHttpVersion($value = 1.0)
    {
        $this->setOption('http', 'protocol_version', (float)$value);
        return $this;
    }

    public function setTimeout($value)
    {
        $this->setOption('http', 'timeout', (float)$value);
        return $this;
    }

    protected function setPostData($value)
    {
        $content = $value;
        if ($value && is_array($value))
            $content = http_build_query($value);

        $this->setOption('http', 'content', $content);
        $this->addHeader([
            'Content_Length' => strlen($content),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        return $this;
    }

    public function getResponseHeaders()
    {
        $headers = [];
        foreach ($this->_responseHeaders as $header) {
            $_header = explode(':', $header, 2);
            if (isset($_header[1])) {
                $key = str_replace('-', '_', strtolower($_header[0]));
                $value = trim($_header[1]);
                if (array_key_exists($key, $headers)) {
                    if (is_array($headers[$key]))
                        $headers[$key][] = $value;
                    else
                        $headers[$key] = [$headers[$key], $value];
                }
                else
                    $headers[$key] = $value;
            }
        }

        return $headers;
    }

    public function getHttpCode()
    {
        foreach ($this->_responseHeaders as $header) {
            $pattern = '/HTTP\/[\d\.]+ (\d+) OK/i';
            if (preg_match($pattern, $header, $matches) === 1)
                return $matches[1];
        }

        return false;
    }
}




class SimpleWrapper
{
    protected $_options;
    protected $_params;
    protected $_context;

    protected $_responseHeaders = [];

    public static function setDefaultOptions($options)
    {
        stream_context_set_default($options);
    }

    public function __construct($options = [], $params = [])
    {
        $this->_options = $options;
        $this->_params = $params;

        $this->_context = $this->createContext();
    }

    /**
     * @param $file string 请求的地址
     * @return string|false string The function returns the read data or false on failure.
     */
    public function request($file)
    {
        $data = file_get_contents($file, null, $this->_context);
        $this->_responseHeaders = $http_response_header;

        return $data;
    }

    public function setOption($wrapper, $option, $value)
    {
        return stream_context_set_option($this->_context, $wrapper, $option, $value);
    }

    public function setOptions($options)
    {
        return stream_context_set_option($this->_context, $options);
    }

    public function getOptions($wrapper = null, $option = null)
    {
        $options = stream_context_get_options($this->_context);
        if ($wrapper)
            if ($option)
                return $options[$wrapper][$option];
            else
                return $options[$wrapper];
        else
            return $options;
    }

    public function setParams($params)
    {
        return stream_context_set_params($this->_context, $params);
    }

    public function getParams($name = null)
    {
        $params = stream_context_get_params($this->_context);
        unset($params['options']);

        return $name ? $params[$name] : $params;
    }

    protected function createContext()
    {
        return stream_context_create($this->_options, $this->_params);
    }
}