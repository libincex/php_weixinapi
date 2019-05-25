<?php
/**
 * Created by PhpStorm.
 * User: libin
 * Date: 2019/5/25
 * Time: 下午1:54
 */

namespace Libincex\WeixinApi;

/**
 * weixin公众号服务端(接收回调请求)
 */
class WeixinMpServer extends WeixinBase
{
    protected $AppID = '';
    protected $token = '';
    protected $encodingAESKey = ''; //消息加解密密钥

    public $MsgCrypt; //加密解密对象
    protected $postObj; //提交的数据对象

    //设置 公众号的服务端通信的token
    function setToken($token)
    {
        $this->token = $token;
    }

    function getToken()
    {
        return $this->token;
    }

    //设置 消息加解密密钥
    function setEncodingAESKey($encodingAESKey)
    {
        $this->encodingAESKey = $encodingAESKey;
    }

    function getEncodingAESKey()
    {
        return $this->encodingAESKey;
    }


    //设置处理服务端事件的类
    protected $respond;

    function setRespondClass($className = 'weixin_server_respond')
    {
        $this->respond = new $className($this);
    }

    //获取传入的数据
    function getData()
    {
        if (!isset($this->postObj)) {
            $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
            empty($postStr) && $postStr = file_get_contents("php://input");
            if (!empty($this->EncodingAESKey)) {
                $this->MsgCrypt = new WXBizMsgCrypt($this->getToken(), $this->getEncodingAESKey(), $this->getAppId());

                $xml = '';
                $this->MsgCrypt->decryptMsg($_REQUEST['msg_signature'], $_REQUEST['timestamp'], $_REQUEST['nonce'], $postStr, $xml);
                $postStr = $xml;
            }

            //解释xml
            $this->postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //转成数组型
            $this->postObj = json_decode(json_encode($this->postObj), 1);
            !is_array($this->postObj) && $this->postObj = array();
        }

        return $this->postObj;
    }

    //开始执行
    function play()
    {
        $postObj = $this->getData();
        if (!empty($postObj)) {

            //根据消息类型分发事件
            switch (trim($postObj['MsgType'])) {
                case 'event': //事件
                    $this->Event($postObj);
                    break;
                case "text": //文本消息
                    $this->respond->text($postObj);
                    break;
                case 'location': //上传位置：纬度 Latitude, 经度 Longitude;
                    $this->respond->location($postObj);

            }
        }

    }

    //接收事件消息
    private function Event($postObj)
    {
        switch ($postObj['Event']) {
            case 'subscribe': //新关注事件
                $this->respond->eventSubscribe($postObj);
                break;
            case 'SCAN': //重复扫码的关注事件
                $this->respond->eventScan($postObj);
                break;
            case 'unsubscribe': //取消关注事件
                $this->respond->eventUnsubscribe($postObj);
                break;
            case 'CLICK': //点击事件
                $this->respond->eventClick($postObj);
                break;
            case 'VIEW': //跳转
                $this->respond->eventView($postObj);
                break;
            case 'LOCATION': //上传位置：纬度 Latitude, 经度 Longitude;
                $this->respond->eventLocation($postObj);
                break;
            case 'WifiConnected': //Wi-Fi连网成功
                $this->respond->eventWifiConnected($postObj);
                break;
            default : //默认

        }
    }

}