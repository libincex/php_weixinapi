<?php
/**
 * 微信公众平台后端服务基类
 * User: libin@163.com
 * Date: 2019/1/24
 * Time: 下午12:27
 */

namespace Libincex\WeixinApi;


class WeixinBase
{
    protected $_app_id;
    protected $_app_secret;

    protected $_access_token;

    public function __construct($app_id = NULL, $app_secret = NULL)
    {
        isset($app_id) && $this->_app_id = $app_id;
        isset($app_secret) && $this->_app_secret = $app_secret;
    }

    /**
     * 获取一个实例
     * @param $app_id
     * @param $app_secret
     * @return static
     */
    public static function getInstance($app_id, $app_secret)
    {
        $apiObj = new static($app_id, $app_secret);
        $apiObj->setAppId($app_id);
        $apiObj->setAppSecret($app_secret);

        return $apiObj;
    }

    /**
     * 设置参数
     * @param $app_id
     */
    public function setAppId($app_id)
    {
        $this->_app_id = $app_id;
    }

    /**
     * 设置参数
     * @param $app_secret
     */
    public function setAppSecret($app_secret)
    {
        $this->_app_secret = $app_secret;
    }

    /** 设置参数
     * @param $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->_access_token = $access_token;
    }

    /**
     * 获取access_token
     * access_token是公众号的全局唯一接口调用凭据，公众号调用各接口时都需使用access_token。开发者需要进行妥善保存。
     * access_token的存储至少要保留512个字符空间。
     * access_token的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的access_token失效。
     * @return string access_token
     */
    protected function getAccessToken()
    {
        if (empty($this->_access_token)) {
            $api_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->_app_id . '&secret=' . $this->_app_secret;
            $s = self::get($api_url);
            $s1 = json_decode($s, true);
            $this->_access_token = $s1['access_token'];
        }

        return $this->_access_token;
    }

    protected static function get($api_url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $s = curl_exec($ch);
        curl_close($ch);

        return $s;
    }

    protected static function post($api_url, $post)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $s = curl_exec($ch);
        curl_close($ch);

        return $s;
    }


}