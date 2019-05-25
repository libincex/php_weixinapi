<?php
namespace Libincex\WeixinApi;

/**
 * 微信服务端响应事件类(根据实际业务，继承此类完善各事件方法
 */
class WeixinMpServerRespond
{
    protected $server;
    protected $postObj;
    protected $MsgCrypt;

    function __construct(WeixinMpServer $server)
    {
        $this->server = $server;
        $this->postObj = $server->getData();
        $this->MsgCrypt = $server->MsgCrypt;
    }

    //回复用户-文本信息
    function transmitText($msg)
    {
        $msg = trim($msg);
        if (empty($msg)) {
            exit;
        }

        $timeStamp = time();
        $data = $this->postObj;
        $xmlTpl = '<xml>
             <ToUserName><![CDATA[%s]]></ToUserName>
             <FromUserName><![CDATA[%s]]></FromUserName>
             <CreateTime>%s</CreateTime>
             <MsgType><![CDATA[text]]></MsgType>
             <Content><![CDATA[%s]]></Content>
         </xml>';
        $replyMsg = sprintf($xmlTpl, $data['FromUserName'], $data['ToUserName'], $timeStamp, $msg);

        if (!empty($this->MsgCrypt)) {
            $encryptMsg = '';
            $this->MsgCrypt->encryptMsg($replyMsg, $timeStamp, uniqid(), $encryptMsg);
            $replyMsg = $encryptMsg;
        }

        exit($replyMsg);
    }

    //回复用户-图片信息
    //参数：$media_id 通过素材管理中的接口上传多媒体文件，得到的id
    function transmitImage($media_id)
    {
        $media_id = trim($media_id);
        if (empty($media_id)) {
            exit();
        }

        $timeStamp = time();
        $data = $this->postObj;

        $replyMsg = "<xml>
        <ToUserName><![CDATA[{$data['FromUserName']}]]></ToUserName>
        <FromUserName><![CDATA[{$data['ToUserName']}]]></FromUserName>
        <CreateTime>{$timeStamp}</CreateTime>
        <MsgType><![CDATA[image]]></MsgType>
        <Image>
            <MediaId><![CDATA[{$media_id}]]></MediaId>
        </Image>
        </xml>";

        if (!empty($this->MsgCrypt)) {
            $encryptMsg = '';
            $this->MsgCrypt->encryptMsg($replyMsg, $timeStamp, uniqid(), $encryptMsg);
            $replyMsg = $encryptMsg;
        }

        exit($replyMsg);
    }

    /*
     * 回复用户-图文信息
     * 参数：$news 图文记录
     * array(
     *  title	否	图文消息标题
        description	否	图文消息描述
        picurl	否	图片链接，支持JPG、PNG格式，较好的效果为大图360*200，小图200*200
        url	否	点击图文消息跳转链接
     * )
     */
    function transmitNews($news)
    {
        if (!is_array($news) || empty($news)) {
            exit();
        }

        $timeStamp = time();
        $data = $this->postObj;
        $n = count($news);
        $replyMsg = "<xml>
        <ToUserName><![CDATA[{$data['FromUserName']}]]></ToUserName>
        <FromUserName><![CDATA[{$data['ToUserName']}]]></FromUserName>
        <CreateTime>{$timeStamp}</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>{$n}</ArticleCount>
        <Articles>";
        foreach ($news as $r) {
            $replyMsg .= "<item>
            <Title><![CDATA[{$r['title']}]]></Title>
            <Description><![CDATA[{$r['description']}]]></Description>
            <PicUrl><![CDATA[{$r['picurl']}]]></PicUrl>
            <Url><![CDATA[{$r['url']}]]></Url>
            </item>";
        }
        $replyMsg .= "</Articles>
        </xml>";

        if (!empty($this->MsgCrypt)) {
            $encryptMsg = '';
            $this->MsgCrypt->encryptMsg($replyMsg, $timeStamp, uniqid(), $encryptMsg);
            $replyMsg = $encryptMsg;
        }

        exit($replyMsg);
    }

    //文本消息
    function text($postObj)
    {

    }

    //上传位置：纬度 Latitude, 经度 Longitude;
    function location($postObj)
    {

    }

    //新关注事件
    function eventSubscribe($postObj)
    {

    }

    //扫二维码事件
    function eventScan($postObj)
    {

    }

    //取消关注事件
    function eventUnsubscribe($postObj)
    {

    }

    //点击事件
    function eventClick($postObj)
    {

    }

    function eventView($postObj)
    {

    }

    //上传位置：纬度 Latitude, 经度 Longitude;
    function eventLocation($postObj)
    {

    }

    //Wi-Fi连网成功
    function eventWifiConnected($postObj)
    {

    }

    //方法重载,用来处理 访问框架的 http 404错误
    function __call($name, $arguments)
    {
        //exit('success');
    }
}