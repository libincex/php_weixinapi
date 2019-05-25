<?php
namespace Libincex\WeixinApi;

/**
 * weixin授权功能
 */
class WeixinOauth2 extends WeixinBase
{
    protected $openid;
    protected $userInfo;


    //网页应用的授权
    //生成weixin用户登录页面的转向URL
    function getUrl($callbackUrl = '')
    {
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        $data = array(
            'appid' => $this->getAppId(),
            'redirect_uri' => trim($callbackUrl),
            'response_type' => 'code',
            'scope' => 'snsapi_userinfo', //snsapi_base , snsapi_userinfo
            'state' => uniqid('wx'),
        );

        return $url . http_build_query($data) . '#wechat_redirect';
    }


    //登录完成后，根据回调传进来的code,检测是否验证成功
    public function getToken($code = NULL)
    {
        $code = trim(isset($code) ? $code : $_REQUEST['code']);
        if (empty($code)) {
            return false;
        }

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
        $data = array(
            'appid' => $this->getAppId(),
            'secret' => $this->getAppSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
        );

        $url = $url . http_build_query($data);
        $redata = WeixinAPI::get($url);
        $redata = json_decode(trim($redata), 1);
        if (!is_array($redata) || empty($redata)) {
            return false;
        }

        if (isset($redata['access_token'])) {
            $this->setAccessToken(trim($redata['access_token']));
            $this->openid = trim($redata['openid']);
        }

        return $this->getAccessToken();
    }


    //获取用户信息
    function userinfo()
    {
        if (!empty($this->userInfo)) {
            //获取用户信息
            $url = 'https://api.weixin.qq.com/sns/userinfo?' . http_build_query(array(
                    'access_token' => $this->getAccessToken(),
                    'openid' => $this->openid, //用户的唯一标识
                    'lang' => 'zh_CN',
                ));
            $redata = WeixinAPI::get($url);
            $redata = json_decode($redata, 1);
            if (!is_array($redata) || empty($redata['openid'])) {
                return [];
            }
        }

        return $this->userInfo;
    }


}
