<?php

namespace frontend\models;

use Yii;

class UserCharge extends \common\models\UserCharge
{
    public function rules()
    {
        return array_merge(parent::rules(), [
            // [['field1', 'field2'], 'required', 'message' => '{attribute} is required'],
        ]);
    }

    public function scenarios()
    {
        return array_merge(parent::scenarios(), [
            // 'scenario' => ['field1', 'field2'],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            // 'field1' => 'description1',
            // 'field2' => 'description2',
        ]);
    }

    //易支付银行卡绑定
    public static function epayBankCard($bankCard)
    {
        // test($bankCard->bank_name);
        $data['ORDER_ID'] = u()->id . date("YmdHis");
        $data['ORDER_TIME'] = date("YmdHis");
        $data['USER_TYPE'] = '02';
        $data['USER_ID'] = EXCHANGE_ID;
        $data['SIGN_TYPE'] = '03';
        $data['BUS_CODE'] = '1011';
        $data['CHECK_TYPE'] = '01';
        $data['ACCT_NO'] = $bankCard->bank_card;  // 卡号
        $data['PHONE_NO'] = $bankCard->bank_mobile; //  手机号
        $data['ID_NO'] = $bankCard->id_card;

        $string = '';
        foreach($data as $key => $v) {
            $string .= "{$key}={$v}&";
        }
        $signSource = $string . EXCHANGE_MDKEY;
        // tes($signSource);
        $mdStr = strtoupper(md5($signSource)); //加密算法第一步大写
        $data['SIGN'] = strtoupper(substr(md5($mdStr . EXCHANGE_MDKEY), 8, 16)); //16位的md5
        $data['NAME'] = $bankCard->bank_user; // 姓名
        $value = '';
        foreach($data as $key => $v) {
            $value .= "{$key}={$v}&";
        }
        $value = substr($value, 0, strlen($value)-1);
        // tes($data, $value);
        // $url = 'http://163.177.40.37:8888/NPS-API/controller/pay';
        $url = 'http://npspay.yiyoupay.net/NPS-API/controller/pay';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        // test($result);
        $str = "<RESP_CODE>0000</RESP_CODE>";
        if(strpos($result,$str)) {
            return true;
        }else {
            return false;
        }
    }

    //云托付
    public static function payYtfchange($amount, $pay_type = "1004")
    {
        //保存充值记录
        $userCharge = new UserCharge();
        $userCharge->user_id = u()->id;
        $userCharge->trade_no = u()->id . date("YmdHis") . rand(1000, 9999);
        $userCharge->amount = $amount;
        $userCharge->charge_type = UserCharge::CHARGE_TYPE_BANKWECHART;
        if($pay_type == '992') {
            $userCharge->charge_type = UserCharge::CHARGE_TYPE_ALIPAY;
            // $amount = 1;
        }
        $userCharge->charge_state = UserCharge::CHARGE_STATE_WAIT;
        if (!$userCharge->save()) {
            return false;
        }
        $url = 'http://pay.yuntuofu.cc/Bank/';
        $data['parter'] = EXCHANGE_ID;
        $data['type'] = $pay_type;
        $data['value'] = $amount;
        $data['orderid'] = $userCharge->trade_no;
        $data['callbackurl'] = url(['site/tynotify'], true);;
        $string = '';
        foreach($data as $key => $v) {
            $string .= "{$key}={$v}&";
        }
        $data['url'] = trim($string, '&') . EXCHANGE_MDKEY;
        $sign = md5($data['url']); 
        $data['sign'] = $sign;
        $data['hrefbackurl'] = url(['site/index'], true);
        return $data;
    }

    //第三方支付 银联支付
    public static function payExtend($amount, $user_id)
    {
        //保存充值记录
        $UserCharge = new UserCharge();
        $UserCharge->user_id = $user_id;
        $UserCharge->trade_no = $user_id . date("YmdHis");
        $UserCharge->amount = $amount;
        $UserCharge->charge_type = UserCharge::CHARGE_TYPE_HUAN;
        $UserCharge->charge_state = UserCharge::CHARGE_STATE_WAIT;
    
        if (!$UserCharge->save()) {
            return false;
        }
        if (0 && System::isMobile()) {
            $url = 'https://mobilegw.ips.com.cn/psfp-mgw/paymenth5.do';
        } else {
            $url = 'https://newpay.ips.com.cn/psfp-entry/gateway/payment.do';
        }
        $MerCode = HX_ID;
        $Account = HX_TID;
        $mercert = HX_MERCERT;
        $MerBillNo = $UserCharge->trade_no;
        $Amount = YII_DEBUG ? '0.01' : $UserCharge->amount . '.00';
        $Date = date('Ymd');
        $GatewayType = '01'; //借记卡：01，信用卡02，IPS账户支付03
        $Merchanturl = WEB_DOMAIN;
        $ServerUrl = WEB_DOMAIN . '/site/notify';// 支付成功回调
        $GoodsName = config('web_name') . '_用户充值';
        $MsgId = 'm'. $MerBillNo;
        $ReqDate = date('Ymdhis');

        $ips = '<Ips><GateWayReq>';
        $body = "<body><MerBillNo>{$MerBillNo}</MerBillNo><Amount>{$Amount}</Amount><Date>{$Date}</Date><CurrencyType>156</CurrencyType ><GatewayType>{$GatewayType}</GatewayType><Lang>GB</Lang><Merchanturl>{$Merchanturl}</Merchanturl><FailUrl></FailUrl><Attach></Attach><OrderEncodeType>5</OrderEncodeType><RetEncodeType>17</RetEncodeType><RetType>1</RetType><ServerUrl>{$ServerUrl}</ServerUrl><BillEXP>1</BillEXP><GoodsName>{$GoodsName}</GoodsName><IsCredit>0</IsCredit><BankCode></BankCode><ProductType>0</ProductType></body>";
        $Signature = md5($body . $MerCode . $mercert);
        $head = "<head><Version>v1.0.0</Version><MerCode>{$MerCode}</MerCode><MerName></MerName><Account>{$Account}</Account><MsgId>{$MsgId}</MsgId><ReqDate>{$ReqDate}</ReqDate><Signature>{$Signature}</Signature></head>";
        $ips .= $head;
        $ips .= $body;
        $ips .= '</GateWayReq></Ips>';
        return ['url' => $url, 'content' => $ips];
        // return $this->render('pay', compact('webAction', 'ips'));
    }
    // 微信支付
    public static function payHxWxpay($amount, $userId)
    {

        //保存充值记录
        $userCharge = new UserCharge(); 
        $userCharge->user_id = $userId;
        $userCharge->trade_no = $userId . date("YmdHis") . rand(1000, 9999);
        $userCharge->amount = $amount;
        $userCharge->charge_type = UserCharge::CHARGE_TYPE_HUAN;
        $userCharge->charge_state = UserCharge::CHARGE_STATE_WAIT;
        if (!$userCharge->save()) {
            return false;
        }  
        $pVersion = 'v1.0.0';//版本号
        $pMerCode = HX_ID;
        $pAccount = HX_TID;
        $pMerCert = HX_MERCERT;
        $pMerName = 'pay';//商户名
        $pMsgId = "msg" . rand(1000, 9999);//消息编号
        $pReqDate = date("Ymdhis");//商户请求时间
        $pMerBillNo = $userCharge->trade_no;//商户订单号
        $pGoodsName = "recharge";//商品名称
        $pGoodsCount = "";
        $pOrdAmt = $userCharge->amount;//订单金额 
        // $pOrdAmt = 0.01;
        $pOrdTime =date("Y-m-d H:i:s");

        $pMerchantUrl = WEB_DOMAIN;
        $pServerUrl = WEB_DOMAIN . '/site/hx-weixin';
        // $pServerUrl = 'http://pay.szsqldjhkjb.top/site/notify';// 支付成功回调
        $pBillEXP="";
        $pReachBy="";
        $pReachAddress="";
        $pCurrencyType="156";
        $pAttach = '用户充值';
        $pRetEncodeType="17";

        $strbodyxml= "<body>"
              ."<MerBillno>".$pMerBillNo."</MerBillno>"
              ."<GoodsInfo>"
              ."<GoodsName>".$pGoodsName."</GoodsName>"
              ."<GoodsCount >".$pGoodsCount."</GoodsCount>"
              ."</GoodsInfo>"
              ."<OrdAmt>".$pOrdAmt."</OrdAmt>"
              ."<OrdTime>".$pOrdTime."</OrdTime>"
              ."<MerchantUrl>".$pMerchantUrl."</MerchantUrl>"
              ."<ServerUrl>".$pServerUrl."</ServerUrl>"
              ."<BillEXP>".$pBillEXP."</BillEXP>"
              ."<ReachBy>".$pReachBy."</ReachBy>"
              ."<ReachAddress>".$pReachAddress."</ReachAddress>"
              ."<CurrencyType>".$pCurrencyType."</CurrencyType>"
              ."<Attach>".$pAttach."</Attach>"
              ."<RetEncodeType>".$pRetEncodeType."</RetEncodeType>"
              ."</body>";
        $Sign = $strbodyxml . $pMerCode . $pMerCert;//签名明文

        $pSignature = md5($strbodyxml.$pMerCode.$pMerCert);//数字签名 
        //请求报文的消息头
        $strheaderxml= "<head>"
               ."<Version>".$pVersion."</Version>"
               ."<MerCode>".$pMerCode."</MerCode>"
               ."<MerName>".$pMerName."</MerName>"
               ."<Account>".$pAccount."</Account>"
               ."<MsgId>".$pMsgId."</MsgId>"
               ."<ReqDate>".$pReqDate."</ReqDate>"
               ."<Signature>".$pSignature."</Signature>"
            ."</head>";

        //提交给网关的报文
        $strsubmitxml =  "<Ips>"
            ."<WxPayReq>"
            .$strheaderxml
            .$strbodyxml
          ."</WxPayReq>"
          ."</Ips>";
          
        $payLinks= '<form style="text-align:center;" action="https://thumbpay.e-years.com/psfp-webscan/onlinePay.do" target="_self" style="margin:0px;padding:0px" method="post" name="ips" >';
        $payLinks  .= "<input type='hidden' name='wxPayReq' value='$strsubmitxml' />";
        $payLinks .= "<input class='btn' type='submit' value='确认支付'></form><script>document.ips2.submit();</script>";
        return ['userCharge' => $userCharge, 'payLinks' => $payLinks];
    }

    //中云第三方支付 ShaoBeiZfb
    public static function payExchange($amount, $acquirer_type = 'WXZF', $tongdao = 'WftWx')
    {
        //保存充值记录
        $userCharge = new UserCharge();
        $userCharge->user_id = u()->id;
        $userCharge->trade_no = u()->id . date("YmdHis") . rand(1000, 9999);
        $userCharge->amount = $amount;
        $userCharge->charge_state = self::CHARGE_STATE_WAIT;
        if ($acquirer_type == 'alipay') {
            $userCharge->charge_type = self::CHARGE_TYPE_ALIPAY;
        }
        if (!$userCharge->save()) {
            return false;
        }
        // test(u()->id);
        // 微信、支付宝交易
        $url = 'http://zy.cnzypay.com/Pay_Index.html';

        $data['pay_memberid'] = ZYPAY_ID; //商户id
        $data['pay_orderid'] = $userCharge->trade_no;
        $data['pay_amount'] = $amount;
        $data['pay_applydate'] = self::$time; //请求时间
        $data['pay_bankcode'] = $acquirer_type; //银行编号
        $data['pay_notifyurl'] = url(['site/notify'], true); //异步回调地址  融智付异步商户url
        $data['pay_callbackurl'] = url(['site/index'], true); //页面返回地址
        // 商户id、应用id、商户订单号、订单金额、加密key
        $string = '';
        ksort($data);
        reset($data);
        foreach($data as $key => $v) {
            $string .= "{$key}=>{$v}&";
        }
        $string .= "key=" . ZYPAY_KEY;
        $data['tongdao'] = $tongdao;
        $data['pay_md5sign'] = strtoupper(md5($string));
        if ($tongdao == 'Gopaywap') {
            $str = '<form id="Form1" name="Form1" method="post" action="' . $url . '">';
            foreach ($data as $key => $val) {
                $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';
            }
            $str = $str . '<input type="hidden" value="提交">';
            $str = $str . '</form>';
            $str = $str . '<script>';
            $str = $str . 'document.Form1.submit();';
            $str = $str . '</script>';
            return $str;
        }
        $result = httpRequest($url, $data);
        preg_match('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', $result, $match);
        if (isset($match[2])) {
            return 'http://zy.cnzypay.com/' . $match[2];
        }
        return false;
    }   
	
    //j云支付
    public static function payRxchange($amount, $pay_type = "30002")
    {   
	    $sxf = $amount*0.02;
		$amounn = $amount-$sxf;
        //保存充值记录
        $userCharge = new UserCharge();
        $userCharge->user_id = u()->id;
        $userCharge->trade_no = u()->id . date("YmdHis") . rand(1000, 9999);
        $userCharge->amount = $amounn;
        $userCharge->charge_type = UserCharge::CHARGE_TYPE_BANKWECHART;
        if($pay_type == '30004') {
            $userCharge->charge_type = UserCharge::CHARGE_TYPE_ALIPAY;
            // $amount = 1;
        }
        $userCharge->charge_state = UserCharge::CHARGE_STATE_WAIT;
        if (!$userCharge->save()) {
            return false;
        }
        $data['parter'] = EXCHANGE_ID;
        $data['type'] = $pay_type;
        $data['value'] = $amount;
        $data['orderid'] = $userCharge->trade_no;
        $data['callbackurl'] = url(['site/tynotify'], true);
        $string = '';
        foreach($data as $key => $v) {
            $string .= "{$key}={$v}&";
        }
        $data['url'] = trim($string, '&') . EXCHANGE_MDKEY;
        $sign = md5($data['url']); 
		$data['urll'] = 'http://pay.1515qp.com/bank/';
        $data['sign'] = $sign;
        $data['hrefbackurl'] = url(['site/index'], true);
        return $data;
    }   

    //qq扫码支付
  public static function payQqschange($data, $type = "bftqqs"){
                  $shopid='999941000494';                        //商户编号
                  $key ='5581CCDCE9DBEF1A34331DBA5F092C50';      //密钥
                  $url='http://paypaul.385mall.top/onlinepay/amalgamateScanCodePay';   //调用接口

                  if($type=='bftqqs'){
                    $corpOrg='QQ';
                    $service='0015';
                    $mode='T1';
                  }elseif($type=='bftwxs'){
                    $corpOrg='WXP';
                    $service='0002';
                    $mode='T1';
                  }elseif($type=='bftzfbs'){
                    $corpOrg='ALP';
                    $service='0010';
                    $mode='T1';
                  }elseif($type=='bftyls'){
                    $corpOrg='Other';
                    $service='010800';
                    $mode='T0';
                  }

        
                  $post=[
                      'amount'=>$data['amount'],
                      // 'amount'=>'0.01',
                      'transCode'=>'001',
                      'service'=>$service,
                      'reqDate'=>date('Ymd'),
                      'reqTime'=>date('His'),
                      'openId'=>'',
                      'requestIp'=>get_client_ipspay(),
                      'dateTime'=>date('YmdHis'),
                      'payChannel'=>$corpOrg,
                      'goodsDesc'=>'',
                      'mode'=>$mode,
                      'goodsName'=>$data['remarks'],
                      'merchantId'=>$shopid,
                      'orderId'=>$data['balance_sn'],
                      'terminalId'=>substr($data['balance_sn'],0,8),
                      'corpOrg'=>$corpOrg,
                      'offlineNotifyUrl'=>"http://".$_SERVER['HTTP_HOST'].":8801/site/qqsnotify",
                         ];

                  //创建签名
                  $sign=UserCharge::createSign($post,$key);
                  ksort($post);
                  $post['sign']=$sign;


                  $jspost=json_encode($post);
                  //启动一个CURL会话
                  $ch = curl_init();
                  // 设置curl允许执行的最长秒数
                  curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                  curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                  curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
                  // 获取的信息以文件流的形式返回，而不是直接输出。
        
                  //发送JSON数据
                  curl_setopt($ch,CURLOPT_HEADER,0);
                  curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json; charset=utf-8','Content-Length:' . strlen($jspost)));

                  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                  //发送一个常规的POST请求。
                  curl_setopt($ch, CURLOPT_POST, 1);
                  curl_setopt($ch, CURLOPT_URL, $url);
                  //要传送的所有数据
                  curl_setopt($ch, CURLOPT_POSTFIELDS, $jspost);
                  // 执行操作
                  $res = curl_exec($ch);
                  // $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                  curl_close($ch);
                  $jsres=json_decode($res);
                  $jsres->order_id=$data['balance_sn'];
                  return $jsres;
 
                  }

    /**
     *创建md5摘要,规则是:按参数名称a-z排序,遇到空值的参数不参加签名。
     */
	public static function createSign($parameters, $key) {
		$signPars = "";
		ksort($parameters);
		foreach($parameters as $k => $v) {
			if("" != $v && "sign" != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}
		$signPars .= "key=" . $key;
        $sign = strtoupper(md5($signPars));
        
        return $sign;
	}	


    //千红支付
    public static function payQhchange($amount, $pay_type = "wx")
    {   
        if($pay_type=="wx"){
            $paytype='901';
        }elseif($pay_type=="zfb"){
            $paytype='904';
        }elseif($pay_type=="kj"){
            //银联钱包
            $paytype='909';
        }elseif($pay_type=="qqs"){
            $paytype='908';
        }elseif($pay_type=="wykj"){
            $paytype='907';
        }else{
            return;
        }
	    // $sxf = $amount*0.02;
		// $amounn = $amount-$sxf;
        //保存充值记录
        $userCharge = new UserCharge();
        $userCharge->user_id = u()->id;
        $userCharge->trade_no = u()->id . date("YmdHis") . rand(1000, 9999);
        //不收手续费
        // $userCharge->amount = $amounn;
        $userCharge->amount = $amount;
        $userCharge->charge_type = $pay_type;
        $userCharge->charge_state = UserCharge::CHARGE_STATE_WAIT;
        if (!$userCharge->save()) {
            return false;
        }

        $pay_memberid = "10141";   //商户ID
        $pay_orderid = $userCharge->trade_no;
        $pay_amount = $amount; //交易金额
        // $pay_amount = '0.11'; //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = url(['site/qhnotify'], true);
        // $data['callbackurl'] = url(['site/qhnotify'], true);
        $pay_callbackurl =url(['site/index'], true);
        //10147
        // $Md5key = 'g7k5ruhmzu071rrbryygu0f0lu2f3krx';
        //10141
 
        $Md5key = "mv0abcj5byp0w7ctu1nd5f31xg3einob";   //密钥
        $tjurl = "http://xxpay.dhdz578.com/Pay_Index.html";   //提交地址
        $pay_bankcode = $paytype;   //银行编码

        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "1234|456";
        $native['pay_productname'] ='会员充值';
 
        $data=array();
        //表单提交
        foreach ($native as $key => $val) {
            // $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';

            $data[$key]=$val;
        }
 
        $data['tjurl']=$tjurl;

        return $data;

        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($native as $key => $val) {
            $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';

        }
        $str = $str . '<input type="submit" value="提交">';
        $str = $str . '</form>';
        $str = $str . '<script>';
        $str = $str . 'document.Form1.submit();';
        $str = $str . '</script>';
        print_r($str);
        die();
        return $str;
   }

    //千红支付
    public static function payQychange($amount, $pay_type = "qyzfbzf")
    {   
        if($pay_type=="qyzfbzf"){
            $paytype='901';
        }// elseif($pay_type=="zfb"){
        //     $paytype='904';
        // }elseif($pay_type=="kj"){
        //     //银联钱包
        //     $paytype='909';
        // }elseif($pay_type=="qqs"){
        //     $paytype='908';
        // }elseif($pay_type=="wykj"){
        //     $paytype='907';
        // }else{
        //     return;
        // }
	    // $sxf = $amount*0.02;
		// $amounn = $amount-$sxf;
        //保存充值记录
        $userCharge = new UserCharge();
        $userCharge->user_id = u()->id;
        $userCharge->trade_no = u()->id . date("YmdHis") . rand(1000, 9999);
        //不收手续费
        // $userCharge->amount = $amounn;
        $userCharge->amount = $amount;
        $userCharge->charge_type = $pay_type;
        $userCharge->charge_state = UserCharge::CHARGE_STATE_WAIT;
        if (!$userCharge->save()) {
            return false;
        }

        header("Content-type:text/html;charset=utf-8");
        $data=$_POST;       //post方式获得表单提交的数据
                      
        $shop_id=2538;         //商户ID，商户在千应官网申请到的商户ID
        $bank_Type=101;   //充值渠道，101表示支付宝快速到账通道
        $bank_payMoney=$amount;     //充值金额
        // $bank_payMoney=1;     //充值金额
        $orderid=$userCharge->trade_no;                  //商户的订单ID，【请根据实际情况修改】
        $callbackurl=url(['site/qynotify'], true);       //商户的回掉地址，【请根据实际情况修改】
        $gofalse=url(['user/index'], true); 
        // $gofalse="http://www.qianyingnet.com/pay";                    //订单二维码失效，需要重新创建订单时，跳到该页
        $gotrue=url(['user/index'], true); //支付成功后，跳到此页面
        $key="d80b987e9c93461fa3289db55c6e0167";                      //密钥
        $posturl='http://www.qianyingnet.com/pay/';                   //千应api的post提交接口服务器地址

        $charset="utf-8";                                              //字符集编码方式
        $token="中文";                                                 //自定义传过来的值 千应平台会返回原值
        $parma='uid='.$shop_id.'&type='.$bank_Type.'&m='.$bank_payMoney.'&orderid='.$orderid.'&callbackurl='.$callbackurl;     //拼接$param字符串
        $parma_key=md5($parma . $key);                                 //md5加密
        $PostUrl=$posturl."?".$parma."&sign=".$parma_key."&gofalse=".$gofalse."&gotrue=".$gotrue."&charset=".$charset."&token=".$token;       //生成指定网址


        //跳转到指定网站
        if (isset($PostUrl)) 
        { 
            header("Location: $PostUrl"); 
            exit;
        }else{
            echo "<script type='text/javascript'>";
            echo "window.location.href='$PostUrl'";
            echo "</script>";	
        };

        die();
        $shop_id= "2538";   //商户ID
        $bank_Type=101;   //充值渠道，101表示支付宝快速到账通道
        $pay_orderid = $userCharge->trade_no;
        $pay_amount = $amount; //交易金额
        // $pay_amount = '0.11'; //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = url(['site/qynotify'], true);
        // $data['callbackurl'] = url(['site/qhnotify'], true);
        $pay_callbackurl =url(['site/index'], true);
        //10147
        // $Md5key = 'g7k5ruhmzu071rrbryygu0f0lu2f3krx';
        //10141
        $gofalse="http://www.qianyingnet.com/pay";                    //订单二维码失效，需要重新创建订单时，跳到该页
        $gotrue="http:/www.qianyingnet.com/";                         //支付成功后，跳到此页面
        $key="d80b987e9c93461fa3289db55c6e0167";                      //密钥
        $posturl='http://www.qianyingnet.com/pay/';                   //千应api的post提交接口服务器地址

        // $parma='uid='.$shop_id.'&type='.$bank_Type.'&m='.$bank_payMoney.'&orderid='.$orderid.'&callbackurl='.$callbackurl;     //拼接$param字符串

        $native = array(
            "uid" => $shop_id,
            "type" => $bank_Type,
            "m" => $pay_amount,
            "orderid" => $pay_orderid,
            "callbackurl" => $pay_notifyurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "1234|456";
        $native['pay_productname'] ='会员充值';
 
        $data=array();
        //表单提交
        foreach ($native as $key => $val) {
            // $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';

            $data[$key]=$val;
        }
 
        $data['tjurl']=$tjurl;

        return $data;

        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($native as $key => $val) {
            $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';

        }
        $str = $str . '<input type="submit" value="提交">';
        $str = $str . '</form>';
        $str = $str . '<script>';
        $str = $str . 'document.Form1.submit();';
        $str = $str . '</script>';
        print_r($str);
        die();
        return $str;
   }


}