<?php
/**
 * Created by PhpStorm.
 * User: libin
 * Date: 2019/5/25
 * Time: 下午1:35
 */

namespace Libincex\WeixinApi;

/*
 * 微信支付操作类

$data = Weixinpay::unifiedorder(array(
    'money'=>10,
    'info'=>'水母A3 2个',
));
print_r($data);
Weixinpay::callback();
exit;

 */
class WeixinPay extends WeixinBase
{
    protected $mchId; //微信支付分配的商户号
    protected $notifyUrl; //支付后的回调网址
    protected $sslCertPath; //ssl证书路径
    protected $sslKeyPath; //ssl证书密钥

    /*
    protected $config = array(
        'appid' => 'wx040bce11917b383c', //微信分配的公众账号ID
        'Api_Key' => '123qwert345gbh2wdkm890ijhssr4578', //API密钥
        'mch_id' => '1240098402', //微信支付分配的商户号
        'notify_url' => 'http://www.coolni.cn/callback.php', //回调网址
    );
    */

    //获取 微信支付分配的商户号
    function getMchId()
    {
        return $this->mchId;
    }

    //设置 微信支付分配的商户号
    function setMchId($mchId)
    {
        $this->mchId = $mchId;
    }

    function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }

    /**
     * 设置ssl证书
     * @param $sslCertPath
     * @param $sslKeyPath
     */
    function setSslCert($sslCertPath, $sslKeyPath)
    {
        $this->sslCertPath = $sslCertPath;
        $this->sslKeyPath = $sslKeyPath;
    }


    /*
    //去微信下单,获取微信的支付id
    //参数:  arr
    [id] 订单id(可省略,默认会自动生成一个订单id)
    [money] 支付的钱数,单位为【分】,要求整数
    [info] 商品描述 32字内
    [attach] 附加数据
    [trade_type] 交易类型: 公众号支付(JSAPI)、原生扫码支付(NATIVE)、app支付(APP)
    [openid] 公众号支付时，此参数必传
    返回: arr
    [id] 生成的订单id
    [prepay_id] 微信平台的支付id
    */
    function unifiedorder($data = array())
    {
        empty($data['id']) && $data['id'] = self::id();
        empty($data['trade_type']) && $data['trade_type'] = 'JSAPI';

        $params = [
            'appid' => $this->getAppId(), //微信分配的公众账号ID
            'Api_Key' => $this->getAppSecret(), //API密钥
            'mch_id' => $this->getMchId(), //微信支付分配的商户号
            'notify_url' => $this->getNotifyUrl(), //回调网址

            'trade_type' => $data['trade_type'], //交易类型
            'attach' => trim($data['attach']), //附加数据
            'nonce_str' => uniqid(), //随机字符串
            'body' => trim($data['info']), //商品描述
            'out_trade_no' => $data['id'], //商户订单号
            'total_fee' => (int)$data['money'], //订单总金额，只能为整数,单位为【分】
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], //APP和网页支付提交用户端ip
        ];

        //公众号支付时，此参数必传
        if (in_array($data['trade_type'], array('JSAPI'))) {
            $params['openid'] = trim($data['openid']);
        }

        $params['sign'] = self::sign($params); //签名
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $redata = self::postXmlCurl($params, $url);
        if ($redata['return_code'] == 'SUCCESS' && !empty($redata['prepay_id'])) {
            $redata['id'] = $params['out_trade_no'];
        } else {
            $redata = array();
        }

        return $redata;
    }

    //验证并获取 回调的返回数据, 用于回调页面
    function callback()
    {
        //计算得出通知验证结果
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        empty($xml) && $xml = file_get_contents("php://input");
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if ($data["return_code"] == "SUCCESS") {
            //交易成功，处理您的数据库
            return array('value' => true, 'text' => '交易成功', 'data' => array(
                'id' => trim($data['out_trade_no']), //商户订单号
                'payId' => trim($data['transaction_id']), //微信支付订单号
                'total_fee' => $data['total_fee'], //金额 (单位: 分)
                'attach' => trim($data['attach']), //附加信息，原样返回
            ));

            echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>"; //请不要修改或删除
        }

        return array('value' => false, 'text' => '交易失败');
    }

    /*
     * 查询订单
     * 参数：$orderId 商户订单号
     * 返回：见 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_2
     */
    function orderquery($orderId)
    {
        $params = array(
            'appid' => $this->getAppId(),
            'mch_id' => $this->getMchId(),
            'nonce_str' => uniqid(), //随机字符串
            'out_trade_no' => trim($orderId), //商户订单号
        );
        $params['sign'] = self::sign($params); //签名

        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $redata = self::postXmlCurl($params, $url);
        if ($redata['return_code'] == 'SUCCESS') {
            $redata['id'] = $params['out_trade_no'];
        } else {
            $redata = array();
        }

        return $redata;
    }

    //获取JS调起微信支付功能的 签名配置等信息
    //参数：prepay_id
    function getWXPayConfig($prepay_id)
    {
        $prepay_id = trim($prepay_id);
        if (empty($prepay_id)) {
            return array();
        }

        $signPackage = array(
            'appId' => $this->getAppId(),
            'timeStamp' => time(),
            'nonceStr' => uniqid(rand(1000000, 9999999)),
            'package' => "prepay_id={$prepay_id}",
            'signType' => 'MD5',
        );

        //支付签名
        $signature = $this->sign($signPackage, 1);
        $signPackage['str'] = $signature;
        $signPackage['paySign'] = strtoupper(md5($signature));

        return $signPackage;
    }

    //生成签名
    function sign($data, $isStr = false)
    {
        if (!is_array($data)) {
            return '';
        }

        unset($data['Api_Key']);
        ksort($data);
        $data['key'] = $this->getAppSecret();
        //print_r($data);
        $uriArr = array();
        foreach ($data as $key => $val) {
            if (!empty($val)) {
                $uriArr[] = "{$key}={$val}";
            }
        }
        $uri = implode('&', $uriArr);

        if ($isStr) {
            return $uri;
        }

        $sign = strtoupper(md5($uri));

        return $sign;
    }


    /**
     * 以post方式提交xml到对应的接口url
     * @param mixed $data 需要post的xml数据
     * @param string $url url
     * @param bool $useCert 是否需要证书，默认不需要
     * @return array|mixed
     */
    function postXmlCurl($data, $url, $useCert = false)
    {
        unset($data['Api_Key']); //过滤

        $xml = self::toXml($data);
        //print_r($xml);exit;
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //url执行超时时间，默认30s
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if ($useCert == true) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $this->sslCertPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $this->sslKeyPath);
        }

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        curl_close($ch);

        //返回结果
        $redata = [];
        if ($data) {
            $redata = json_decode(json_encode(simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            !is_array($redata) && $redata = [];
        }
        
        return $redata;
    }

    //转换参数,微信服务端需要用的xml格式
    public static function toXml($data = array())
    {
        $xml = "<xml>";
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_numeric($val)) {
                    $xml .= "<{$key}>{$val}</{$key}>\n";
                } else {
                    $xml .= "<{$key}><![CDATA[{$val}]]></{$key}>\n";
                }
            }
        }
        $xml .= "</xml>";

        return $xml;
    }

    //生成订单号
    public static function id()
    {
        $time = gettimeofday();
        return date('YmdHis') . substr('00000' . $time['usec'], -6, 6); //时间戳+当前毫微秒数
    }

}