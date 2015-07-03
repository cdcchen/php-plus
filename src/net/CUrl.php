<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/4
 * Time: 下午5:58
 */

namespace phpplus\net;

class CUrl
{
    public static $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 100,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_DNS_USE_GLOBAL_CACHE => true,
        CURLOPT_FORBID_REUSE => true,
    ];

    private $_ch;
    private $_responseHeaders = [];
    private $_requestHeaders = [];
    private $_data;

    public function __construct($options = [])
    {
        $this->_ch = curl_init();
        $this->setOption(static::$defaultOptions);
        $this->setOption($options);
    }

    public function debug($value = false)
    {
        return $this->setOption(CURLOPT_VERBOSE, (bool)$value);
    }

    public function setOption($option, $value = null)
    {
        if (is_int($option) && $value !== null)
            curl_setopt($this->_ch, $option, $value);
        elseif (is_array($option))
            curl_setopt_array($this->_ch, $option);
        else
            throw new \InvalidArgumentException('$option type must be int or array');

        return $this;
    }

    public function getInfo($opt = null)
    {
        // @todo 如果opt为null时curl_getinfo应该返回所有info，但此处却返回false，标记一下。暂时不传递$opt处理
        return $opt ? curl_getinfo($this->_ch, $opt) : curl_getinfo($this->_ch);
    }

    public function getHttpCode()
    {
        return (int)$this->getInfo(CURLINFO_HTTP_CODE);
    }

    public function getResponseHeaders($key = null)
    {
        return $key === null ?  $this->_responseHeaders : $this->_responseHeaders[$key];
    }


    public function getHttpRawCookies()
    {
        return $this->getResponseHeaders('set_cookie');
    }

    public function getHttpCookies()
    {
        $data = [];

        $cookies = (array)$this->getResponseHeaders('set_cookie');
        foreach ($cookies as $cookie) {
            $data =  array_merge($data, static::parseCookie($cookie));
        }

        return $data;
    }

    public function getRawData()
    {
        return $this->_data;
    }

    public function getBody()
    {
        if ($this->_responseHeaders) {
            $body = explode("\r\n\r\n", $this->_data, 2);
            return $body[1];
        }
        else
            return $this->_data;
    }

    public function getJsonData($assoc = true, $depth = 512, $options = 0)
    {
        return json_decode($this->getBody(), $assoc, $depth, $options);
    }

    public function getXmlData()
    {
        return simplexml_load_string($this->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    public function getErrno()
    {
        return curl_errno($this->_ch);
    }

    public function getError()
    {
        return curl_error($this->_ch);
    }

    public function execute($url = null)
    {
        if ($url)
            $this->setOption(CURLOPT_URL, $url);

        $this->_data = curl_exec($this->_ch);

        return $this;
    }

    public function get($url, $data = null)
    {
        $url = static::httpBuildUrl($url, $data);
        $this->setOption([CURLOPT_POST => false]);
        return $this->execute($url);
    }

    public function head($url, $data = null)
    {
        $url = static::httpBuildUrl($url, $data);
        $this->setOption([
            CURLOPT_POST => false,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
        ]);
        return $this->execute($url);
    }

    public function options($url, $data = null)
    {
        $url = static::httpBuildUrl($url, $data);
        $this->setOption([
            CURLOPT_POST => false,
            CURLOPT_CUSTOMREQUEST => 'OPTIONS',
        ]);
        return $this->execute($url);
    }

    public function post($url, $data = null)
    {
        $data = is_array($data) ? http_build_query($data) : $data;
        $this->setOption([
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
        ]);
        $this->setHttpHeaders(['Content-Length' => strlen($data)]);

        return $this->execute($url);
    }

    /**
     * @param $url
     * @param resource|string $body
     * @return CUrl
     */
    public function put($url, $body)
    {
        $options = [
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
        ];

        if (is_resource($body)) {
            $length = (int)fstat($body)['size'];
            $options[CURLOPT_PUT] = true;
            $options[CURLOPT_INFILE] = $body;
            $options[CURLOPT_INFILESIZE] = $length;
        }
        else {
            $data = is_array($body) ? http_build_query($body) : $body;
            $options[CURLOPT_POSTFIELDS] = $data;
        }

        $this->setOption($options);

        return $this->execute($url);
    }

    public function patch($url, $data = null)
    {
        $data = is_array($data) ? http_build_query($data) : $data;
        $this->setOption([
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $data,
        ]);
        $this->setHttpHeaders(['Content-Length' => strlen($data)]);

        return $this->execute($url);
    }

    public function delete($url, $data = null)
    {
        $data = is_array($data) ? http_build_query($data) : $data;
        $this->setOption([
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        ]);
        $this->setHttpHeaders(['Content-Length' => strlen($data)]);

        return $this->execute($url);
    }

    public function returnHeaders($flag = true)
    {
        $this->setOption([
            CURLOPT_HEADER => (bool)$flag,
        ]);

        if ($flag) {
            $this->setOption([
                CURLOPT_HEADERFUNCTION => [$this, '_get_headers']
            ]);
        }

        return $this;
    }

    public function setReferrer($referrer = true)
    {

        if (is_bool($referrer))
            $this->setOption(CURLOPT_AUTOREFERER, $referrer);
        elseif (is_string($referrer)) {
            $this->setOption([CURLOPT_AUTOREFERER => false, CURLOPT_REFERER => $referrer]);
        }

        return $this;
    }

    public function setHttpHeaders(array $headers = [])
    {
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                $pos = stripos(trim($value), ':');
                $key = trim(substr($value, 0, $pos));
                $value = trim(substr($value, $pos + 1));
            }
            $this->_requestHeaders[$key] = $value;
        }

        $headers = array_map(function($key, $value){
            return "{$key}: {$value}";
        }, array_keys($this->_requestHeaders), array_values($this->_requestHeaders));

        $this->setOption(CURLOPT_HTTPHEADER, $headers);

        return $this;
    }

    public function setHttpBasicAuth($username, $password)
    {
        if (!empty($username)) {
            return $this->setOption([
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => "{$username}:{$password}"
            ]);
        }
        else
            throw new \InvalidArgumentException('username is required.');
    }

    public function setUserPassword($username, $password)
    {
        return $this->setOption(CURLOPT_USERPWD, "{$username}:{$password}");
    }


    public function setUserAgent($agent)
    {
        if ($agent)
            $this->setOption(CURLOPT_USERAGENT, $agent);

        return $this;
    }

    public function setCookie($cookie)
    {
        if (is_array($cookie)) {
            $data = [];
            foreach ($cookie as $key => $value)
                $data[] = $key . '=' . $value;

            $cookie = join(';', $data);
        }

        if ($cookie)
            $this->setOption(CURLOPT_COOKIE, $cookie);

        return $this;
    }

    public function setCookieFile($file, $jar = null)
    {
        if ($file && is_writable($file) && is_readable($file)) {
            return $this->setOption([
                CURLOPT_COOKIEFILE => $file,
                CURLOPT_COOKIEJAR => $jar ?: $file,
            ]);
        }
        else
            throw new \InvalidArgumentException('file is required and writable and readable.');
    }

    public function setFollowLocation($value = true, $maxRedirects=  5)
    {
        return $this->setOption([
            CURLOPT_AUTOREFERER => (bool)$value,
            CURLOPT_FOLLOWLOCATION => (bool)$value,
            CURLOPT_MAXREDIRS => $maxRedirects,
        ]);
    }

    public function setAllowUpload($value = true, $safe = true)
    {
        return $this->setOption([
            CURLOPT_UPLOAD => (bool)$value,
            CURLOPT_SAFE_UPLOAD => (bool)$safe,
        ]);
    }

    public function setHttpVersion($version)
    {
        return $this->setOption(CURLOPT_HTTP_VERSION, $version);
    }

    public function setExecTimeout($value, $ms = true)
    {
        return $this->setOption($ms ? CURLOPT_TIMEOUT_MS : CURLOPT_TIMEOUT, $value);
    }

    public function setHttpEncoding($value)
    {
        return $this->setOption(CURLOPT_ENCODING, $value);
    }

    public function setShare($sh)
    {
        if (!defined('CURLOPT_SHARE'))
            throw new \Exception('CURLOPT_SHARE require php version is 5.5 or later');

        return $this->setOption(CURLOPT_SHARE, $sh);
    }

    private static function httpBuildUrl($url, $params)
    {
        if (empty($params)) return $url;

        $info = parse_url($url);
        parse_str($info['query'], $query);
        $query = http_build_query(array_merge($query, $params));

        if (function_exists('http_build_url')) {
            $info['query'] = $query;
            $url = http_build_url($url, $info);
        }
        else
            $url .= '?' . $query;

        return $url;
    }

    public function close()
    {
        curl_close($this->_ch);
    }

    public function setSSL($peer = false, $host = 2, array $extraOptions = [])
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $peer);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, $host);
        $this->setOption($extraOptions);
        return $this;
    }

    public function setEnableSessionCookie($value = false)
    {
        return $this->setOption(CURLOPT_COOKIESESSION, (bool)$value);
    }

    public function reset()
    {
        curl_reset($this->_ch);
        $this->_responseHeaders = [];
        $this->_requestHeaders = [];
        $this->setHttpHeaders([]);
        $this->_data = null;

        return $this;
    }

    public function getHandler()
    {
        return $this->_ch;
    }

    public function copy()
    {
        return curl_copy_handle($this->_ch);
    }

    public static function setDefaultOptions($options = [])
    {
        self::$defaultOptions = static::mergeOptions(self::$defaultOptions, $options);
    }

    protected function _get_headers($handler, $header)
    {
        $_header = explode(':', $header, 2);
        if (isset($_header[1])) {
            $key = strtolower($_header[0]);
            $value = trim($_header[1]);
            if (array_key_exists($key, $this->_responseHeaders)) {
                if (is_array($this->_responseHeaders[$key]))
                    $this->_responseHeaders[$key][] = $value;
                else
                    $this->_responseHeaders[$key] = [$this->_responseHeaders[$key], $value];
            }
            else
                $this->_responseHeaders[$key] = $value;
        }
        return strlen($header);
    }

    protected static function mergeOptions(array $options, array $options1)
    {
        foreach ($options1 as $index => $value)
            $options[$index] = $value;

        return $options;
    }

    protected static function parseCookie($cookie)
    {
        $data = [];
        $split = explode(';', $cookie);
        foreach( $split as $field ) {
            list($key, $value) = explode('=', trim($field));
            $key = trim($key);

            if(!in_array( $key, array('domain', 'expires', 'path', 'secure', 'httponly', 'comment')))
                $data[$key] = $value;
        }

        return $data;
    }

}