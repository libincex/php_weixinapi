<?php

namespace Libincex\WeixinApi;

/**
 * 微信操作类
 * Class WeixinApi
 * @package Libincex\WeixinApi
 */
class WeixinApi
{
    //对象池
    private static $oauth = []; //微信登录接入
    private static $mp = []; //公众号
    private static $server = []; //公众号服务端
    private static $app = []; //小程序
    private static $pay = []; //微信支付

    private $AppID = '';
    private $AppSecret = '';

    function __construct($AppID, $AppSecret)
    {
        $this->AppID = trim($AppID);
        $this->AppSecret = trim($AppSecret);
    }

    //获取授权对象
    function getOauth()
    {
        if (empty(self::$oauth[$this->AppID])) {
            self::$oauth[$this->AppID] = WeixinOauth2::getInstance($this->AppID, $this->AppSecret);
        }

        return self::$oauth[$this->AppID];
    }

    //获取公众号对象
    function getMp()
    {
        if (empty(self::$mp[$this->AppID])) {
            self::$mp[$this->AppID] = WeixinMp::getInstance($this->AppID, $this->AppSecret);
        }

        return self::$mp[$this->AppID];
    }

    //公众号服务端(接收回调请求)
    function getServer($token, $encodingAESKey)
    {
        if (empty(self::$server[$this->AppID])) {
            $server = WeixinMpServer::getInstance($this->AppID, '');
            $server->setToken($token);
            $server->setEncodingAESKey($encodingAESKey);
            self::$server[$this->AppID] = $server;

            //服务端验证
            if (!empty($_REQUEST['echostr'])) {
                $server->valid();
            }
        }

        return self::$server[$this->AppID];
    }

    //获取小程序对象
    function getApp()
    {
        if (empty(self::$app[$this->AppID])) {
            self::$app[$this->AppID] = WeixinApp::getInstance($this->AppID, $this->AppSecret);
        }

        return self::$server[$this->AppID];
    }

    //获取微信支付对象
    function getPay($mchId, $notifyUrl, $sslCertPath = '', $sslKeyPath = '')
    {
        if (empty(self::$pay[$this->AppID])) {
            $pay = WeixinPay::getInstance($this->AppID, $this->AppSecret);
            $pay->setMchId($mchId);
            $pay->setNotifyUrl($notifyUrl);
            $pay->setSslCert($sslCertPath, $sslKeyPath);

            self::$pay[$this->AppID] = $pay;
        }

        return self::$pay[$this->AppID];
    }



    //curl post提交数据
    public static function post($url, $data = array(), $timeout = 10, $httpheader = array())
    {
        //post的数据中是否包含文件
        if (is_array($data)) {
            $hasFile = false;
            foreach ($data as $val) {
                if ($val{0} == '@') {
                    $hasFile = true;
                    break;
                }
            }

            !$hasFile && $data = http_build_query($data);
        }

        $curl = curl_init(); //初始化CURL句柄
        curl_setopt($curl, CURLOPT_URL, $url); //设置请求的URL
        //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);//启用POST提交
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); //设置POST提交的字符串
        !empty($httpheader) && curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);//设置HTTP头信息

        //设定为不验证证书和HOST
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, (int)$timeout); //设置连接超时的秒数

        $content = curl_exec($curl); //执行预定义的CURL
        curl_close($curl); //释放curl句柄

        return $content;
    }

    /*
    //上传文件
    //参数：
    $url 上传URL
    $data = array(
        'key1'=>array('file'=>$filepath,'filename'=>'定义文件名称','type'=>'Content-Type'),
        'key2'=>......
    );
    */
    public static function postFile($url, $data = array())
    {
        $postdata = array();
        foreach ($data as $key => $val) {
            $keyname = "{$key}\"; filename=\"{$val['filename']}\r\nContent-Type: {$data['type']}\r\n";
            $postdata[$keyname] = file_get_contents($val['file']);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    //curl get
    public static function get($url, $proxy = null, $timeout = 10)
    {
        if (!$url) return false;
        $ssl = substr($url, 0, 8) == 'https://' ? true : false;
        $curl = curl_init();
        if (!is_null($proxy)) curl_setopt($curl, CURLOPT_PROXY, $proxy);
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
            //curl_setopt($curl, CURLOPT_SSL_VERIF/YHOST, 1); // 从证书中检查SSL加密算法是否存在
        }

        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); //在HTTP请求中包含一个"User-Agent: "头的字符串。
        //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:text/plain; charset=utf-8'));
        curl_setopt($curl, CURLOPT_HEADER, 0); //启用时会将头文件的信息作为数据流输出。
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //文件流形式
        curl_setopt($curl, CURLOPT_TIMEOUT, (int)$timeout); //设置cURL允许执行的最长秒数。
        $content = curl_exec($curl);
        $response_headers = curl_getinfo($curl);

        curl_close($curl);
        if ($response_headers['http_code'] == '200') {
            return $content;
        } else {
            return false;
        }
    }

    //过滤emoji表情
    public static function filterEmoji($str)
    {
        $pattern = "#(\\\ud[0-9a-f]{3})|(\\\ue[0-9a-f]{3})#ie";
        $jsonStr = json_encode($str);
        $jsonStr = preg_replace($pattern, '', $jsonStr);

        return json_decode($jsonStr);
    }
}