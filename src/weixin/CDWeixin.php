<?php

namespace phpplus\weixin;

class CDWeixin
{
    const MSG_TYPE_TEXT = 'text';
    const MSG_TYPE_LOCATION = 'location';
    const MSG_TYPE_IMAGE = 'image';
    const MSG_TYPE_LINK = 'link';
    const MSG_TYPE_EVENT = 'event';
    const MSG_EVENT_SUBSCRIBE = 'subscribe';
    const MSG_EVENT_UNSUBSCRIBE = 'unsubscribe';
    const MSG_EVENT_MENUCLICK = 'click';
    
    const REPLY_TYPE_TEXT = 'text';
    const REPLY_TYPE_NEWS = 'news';
    const REPLY_TYPE_MUSIC = 'music';

    /**
     * 接收到的post数据
     * @var object
     */
    protected  $_data;
    private $_token;
    
    public function __construct($token)
    {
        if (empty($token))
            throw new WeixinException('Token is required');
        
        if (method_exists($this, 'errorHandler'))
            set_error_handler(array($this, 'errorHandler'));
        
        if (method_exists($this, 'exceptionHandler'))
            set_exception_handler(array($this, 'exceptionHandler'));
        
        $this->_token = $token;
        $this->parsePostRequestData();
        $this->run();
    }
    
    public function run()
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            if ($this->_data && $this->beforeProcess() === true) {
                if ($this->isSubscribeEvent())
                	$this->subscribe();
                elseif ($this->isUnSubscribeEvent())
                    $this->unsubscribe();
                else
                    $this->processRequest();
                
                $this->afterProcess();
            }
            else
                throw new WeixinException('POST 数据不正确或者beforeProcess方法没有返回true');
        }
        else
            $this->sourceCheck();
        
        exit(0);
    }
    
    /**
     * 判断是否是文字消息
     * @return boolean
     */
    public function isTextMsg()
    {
        return strtolower($this->_data->MsgType) == self::MSG_TYPE_TEXT;
    }
    
    /**
     * 判断是否是位置消息
     * @return boolean
     */
    public function isLocationMsg()
    {
        return strtolower($this->_data->MsgType) == self::MSG_TYPE_LOCATION;
    }
    
    /**
     * 判断是否是图片消息
     * @return boolean
     */
    public function isImageMsg()
    {
        return strtolower($this->_data->MsgType) == self::MSG_TYPE_IMAGE;
    }
    
    /**
     * 判断是否是链接消息
     * @return boolean
     */
    public function isLinkMsg()
    {
        return strtolower($this->_data->MsgType) == self::MSG_TYPE_IMAGE;
    }
    
    /**
     * 判断是否是事件消息
     * @return boolean
     */
    public function isEventMsg()
    {
        return strtolower($this->_data->MsgType) == self::MSG_TYPE_EVENT;
    }
    
    /**
     * 判断是否是事件消息中的进入subscribe事件
     * @return boolean
     */
    public function isSubscribeEvent()
    {
        return $this->isEventMsg() && strtolower($this->_data->Event) == self::MSG_EVENT_SUBSCRIBE;
    }
    
    /**
     * 判断是否是事件消息中的进入unsubscribe事件
     * @return boolean
     */
    public function isUnSubscribeEvent()
    {
        return $this->isEventMsg() && strtolower($this->_data->Event) == self::MSG_EVENT_UNSUBSCRIBE;
    }
    
    /**
     * 判断是否是事件消息中的进入menu click事件
     * @return boolean
     */
    public function isClickEvent()
    {
        return $this->isEventMsg() && strtolower($this->_data->Event) == self::MSG_EVENT_MENUCLICK;
    }

    /**
     * 生成向用户发送的文字信息
     * @param string $content
     * @return string xml字符串
     */
    public function outputText($content)
    {
        $textTpl = '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <Content><![CDATA[%s]]></Content>
            </xml>';
    
        $text = sprintf($textTpl, $this->_data->FromUserName, $this->_data->ToUserName, time(), self::REPLY_TYPE_TEXT, $content);
        return $text;
    }

    /**
     * 生成向用户发送的文字图片字符串
     * @param string $content
     * @param array $posts 文章数组，每一个元素是一个文章数组，索引跟微信官方接口说明一致
     * @throws WeixinException
     * @return string xml字符串
     */
    public function outputNews($content, $posts = array())
    {
        $textTpl = '<xml>
             <ToUserName><![CDATA[%s]]></ToUserName>
             <FromUserName><![CDATA[%s]]></FromUserName>
             <CreateTime>%s</CreateTime>
             <MsgType><![CDATA[%s]]></MsgType>
             <Content><![CDATA[%s]]></Content>
             <ArticleCount>%d</ArticleCount>
             <Articles>%s</Articles>
         </xml>';
        
        $itemTpl = '<item>
             <Title><![CDATA[%s]]></Title>
             <Description><![CDATA[%s]]></Description>
             <PicUrl><![CDATA[%s]]></PicUrl>
             <Url><![CDATA[%s]]></Url>
         </item>';
        
        $items = '';
        foreach ((array)$posts as $p) {
            if (is_array($p))
                $items .= sprintf($itemTpl, $p['Title'], $p['Discription'], $p['PicUrl'], $p['Url']);
            else
                throw new WeixinException('$posts 数据结构错误');
        }
        
        $text = sprintf($textTpl, $this->_data->FromUserName, $this->_data->ToUserName, time(), self::REPLY_TYPE_NEWS, $content, count($posts), $items);
        return $text;
    }
    
    public function outputMusic($title, $desc, $music_url, $hq_music_url)
    {
        $textTpl = '<xml>
             <ToUserName><![CDATA[%s]]></ToUserName>
             <FromUserName><![CDATA[%s]]></FromUserName>
             <CreateTime>%s</CreateTime>
             <MsgType><![CDATA[%s]]></MsgType>
             <Music>
                 <Title><![CDATA[%s]]></Title>
                 <Description><![CDATA[%s]]></Description>
                 <MusicUrl><![CDATA[%s]]></MusicUrl>
                 <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
             </Music>
         </xml>';
        
        $text = sprintf($textTpl, $this->_data->FromUserName, $this->_data->ToUserName, time(), self::REPLY_TYPE_MUSIC, $title, $desc, $music_url, $hq_music_url);
        return $text;
    }
    
    /**
     * 解析接收到的post数据
     * @return \SimpleXMLElement
     */
    public function parsePostRequestData()
    {
        $rawData = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = simplexml_load_string($rawData, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($data !== false)
            $this->_data = $data;
    
        return $data;
    }
    
    /**
     * 返回接收到的post数组
     * @return object
     */
    public function getPostData()
    {
        return $this->_data;
    }
    
    protected function beforeProcess()
    {
        return true;
    }
    
    protected function afterProcess()
    {
    }

    protected function processRequest()
    {
        throw new WeixinException('此方法必须被重写');
    }
    
    protected function subscribe()
    {
        throw new WeixinException('此方法必须被重写');
    }
    
    protected function unsubscribe()
    {
        throw new WeixinException('此方法必须被重写');
    }
    
    /**
     * 验证url来源是否证确
     * @return boolean
     */
    private function checkSignature()
    {
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
    
        $params = array($this->_token, $timestamp, $nonce);
        sort($params);
        $sig = sha1(implode($params));
    
        return $sig == $signature;
    }
    
    private function sourceCheck()
    {
        if ($this->checkSignature()) {
            $echostr = htmlspecialchars($_GET['echostr']);
            echo $echostr;
        }
        else
            throw new WeixinException('签名不正确');
    
        exit(0);
    }
}

class WeixinException extends \Exception
{

}