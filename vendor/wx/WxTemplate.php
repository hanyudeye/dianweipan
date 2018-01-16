<?php
class WxTemplate
{
    //获取微信用户信息
    public function getWechatUser($code) {   //以snsapi_userinfo为scope发起的网页授权，是用来获取用户的基本信息的
        //第二步：通过code换取网页授权 
        $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . WX_APPID . '&secret=' . WX_APPSECRET . '&code=' . $code . '&grant_type=authorization_code';
        $tokenArr = $this->getContents($tokenUrl);
        if (isset($tokenArr['errcode'])) {
            test('token指令已经失效！');
        }

        //第三步拉用户信息
        $infoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $tokenArr['access_token'] . '&openid=' . $tokenArr['openid'] . '&lang=zh_CN';
        return $this->getContents($infoUrl);
    }

    //file_get_contents方法发送
    public function getContents($url){
        $str = file_get_contents($url);
        $res = $this->is_json($str);
        if ($res) {
            return json_decode($str, true);
        } else {
            return $str;
        }
    }

    public function is_json($string) { 
        json_decode($string); 
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function getAccessToken()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.WX_APPID.'&secret='.WX_APPSECRET;
        $options = [
            CURLOPT_SSL_VERIFYPEER => false,    
            CURLOPT_SSL_VERIFYHOST => false,    
        ];
        $res = $this->curlRequest($url, null, $options);
        if (!empty($res->access_token)) {
            return $res->access_token;
        } else {
            return false;
        }
    }

    /**
     * 通用curl请求，支持GET/POST
     * 
     * @param mixed $url        Request URL 
     * @param mixed $data       Post data 
     * @param mixed $options    Curl options 
     * @return string
     */
    protected function curlRequest($url, $data = [], $options = [])
    {
        $ret = false;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, true);
        }
        $defaultOptons = [
            CURLOPT_RETURNTRANSFER => true,
        ];
        //这里只能用+, 并且options要放在前面
        $options =  (array)$options + $defaultOptons;
        foreach ((array)$options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $ret = json_decode(curl_exec($ch));
        curl_close($ch);

        return $ret;
    }
    
    public  function wx_get_jsapi_ticket($token){
        $ticket = "";
        do {
            if (empty($token)) {
                exit("get access token error.");
                break;
            }
            $options = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ];
            $url = sprintf('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi', $token);
            $res = $this->curlRequest($url, null, $options);
            if ($res->errcode ==0) {
                $ticket = $res->ticket;
            } else {
                // 有错误
                return false;
            }
            session('WxTicket', $ticket, 7000);
        } while(0);
        return $ticket;
    }

    public function getWxInfo($url, $token, $prepay_id) {
        $timestamp = (String) time();
        $wxnonceStr = (String) rand(1000,9999);
        $wxticket = $this->wx_get_jsapi_ticket($token);
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $wxSha1 = sha1($wxOri);
        //正式账号获取
        $sharejson = ['wxOri'=>$wxOri, 'ticket'=>$wxticket, 'appid'=>WX_APPID, 'timestamp'=>$timestamp, 'noncestr'=>$wxnonceStr, 'signature'=>$wxSha1];

        $sign = $this->getSign($timestamp, $wxnonceStr, $prepay_id);
        //测试账号获取
        // $sharejson = ['wxOri'=>$wxOri,'ticket'=>$wxticket,'appid'=>'wx7b4153724929dbc3','timestamp'=>$timestamp,'noncestr'=>$wxnonceStr,'signature'=>$wxSha1];
        return [$sharejson, $sign];
    }

    protected function getSign($timestamp, $wxnonceStr, $prepay_id)
    {
        $appId = WX_APPID;
        $timeStamp = $timestamp;
        $nonceStr = $wxnonceStr;
        $package = 'prepay_id=' . $prepay_id;
        $signType = 'MD5';
        $sign = sprintf("appId=%s&nonceStr=%s&package=%s&signType=%s&timeStamp=%s&key=" . WX_KEY, $appId, $nonceStr, $package, $signType, $timeStamp);
        $sign = strtoupper(md5($sign));
        return $sign;
    }

    //获取微信config接口注入权限验证配置
    public function getWxConfig($url) {
        $timestamp = (String) time();
        $wxnonceStr = (String) rand(1000,9999);
        if (($wxticket = session('WxTicket')) == null) {
            if (($access_token = session('WxAccessToken')) == null) {
                $access_token = $this->getAccessToken();
                session('WxAccessToken', $access_token, 7000);
            }
            $wxticket = $this->wx_get_jsapi_ticket($access_token);
            session('WxTicket', $wxticket, 7000);
        }
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $wxSha1 = sha1($wxOri);

        return ['wxOri'=>$wxOri, 'ticket'=>$wxticket, 'appid'=>WX_APPID, 'timestamp'=>$timestamp, 'noncestr'=>$wxnonceStr, 'signature'=>$wxSha1];
    }  
}
