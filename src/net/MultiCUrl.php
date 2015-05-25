<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/4
 * Time: 下午5:58
 */

namespace phpplus\net;


class MultiCUrl
{
    public static $defaultOptions = [
    ];

    private $_mh;
    private $_chs = [];

    public function __construct()
    {
        return $this->_mh = curl_multi_init();
    }

    public function setOption($options, $value = null)
    {
        if (is_int($options) && $value !== null)
            curl_multi_setopt($this->_mh, $options, $value);
        elseif (is_array($options))
            foreach ($options as $option => $value)
                $this->setOption($this->_mh, $option, $value);
        else
            throw new \InvalidArgumentException('$option type must be int or array');

        return $this;
    }

    public function addHandler($chs)
    {
        if (is_array($chs)) {
            foreach ($chs as $ch)
                $this->addHandler($ch);
        }
        else {
            curl_multi_add_handle($this->_mh, $chs);
            $this->_chs[(int)$chs] = $chs;
        }

        return $this;
    }

    public function removeHandler($chs)
    {
        if (is_array($chs)) {
            foreach ($chs as $ch)
                $this->removeHandler($ch);
        }
        else {
            curl_multi_remove_handle($this->_mh, $chs);
            unset($this->_chs[(int)$chs]);
        }

        return $this;
    }

    public function getHandler()
    {
        return $this->_mh;
    }

    public function execute($timeout = 1.0)
    {
        $running = false;
        do {
            do {
                $mrc = curl_multi_exec($this->_mh, $running);
            }
            while ($mrc === CURLM_CALL_MULTI_PERFORM);

            if ($mrc != CURLM_OK) break;

            // do something

            if ($running)
                curl_multi_select($this->_mh, $timeout);
        }
        while ($mrc === CURLM_CALL_MULTI_PERFORM || $running);

        return true;
    }

    public function getContent()
    {
        $data = [];
        foreach ($this->_chs as $ch)
            $data[] = curl_multi_getcontent($ch);

        return $data;
    }

    public function close()
    {
        $this->removeHandler($this->_chs);
        curl_multi_close($this->_mh);
    }

    public function getError($errno)
    {
        return curl_multi_strerror($errno);
    }

}
