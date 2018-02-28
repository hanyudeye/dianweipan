<?php

namespace frontend\controllers;

use Yii;
use common\helpers\Curl;
use frontend\models\User;
use frontend\models\UserCoupon;
use frontend\models\Product;
use frontend\models\Order;
use frontend\models\ProductPrice;
use frontend\models\DataAll;
use frontend\models\UserCharge;
use common\helpers\FileHelper;
use common\helpers\Json;
use admin\models\Retail;

class SiteController extends \frontend\components\Controller
{
    public function beforeAction($action)
    {
        return true;
        if (!parent::beforeAction($action)) {
            return false;
        } else {
            $actions = ['ajax-update-status', 'wxtoken', 'wxcode', 'test', 'rule', 'captcha','notify', 'hx-weixin', 'zynotify', 'update-user', 'update', 'tynotify','qhnotify','qynotify'];
            if (user()->isGuest && !in_array($this->action->id, $actions)) {
                $wx = session('wechat_userinfo');
                if (!empty($wx)) {
                    $user = User::find()->where(['open_id' => $wx['openid']])->one();
                    $user->login(false);
                } else {
                    $code = get('code');
                    if (empty($code)) {
                        $this->redirect(['/wechart.php']);
                        return false;
                    } else {
                        User::registerUser($code);
                    }
                }
            }
            return true;
        }
    }
    //商城首页
    public function actionShop()
    {
        $this->view->title = '商城';
        return $this->render('shop');
    }

    //商城--商品详情1
    public function actionOne()
    {
        $this->view->title = '商品详情';
        return $this->render('shopDetail1');
    }
    //商城--商品详情2
    public function actionTwo()
    {
        $this->view->title = '商品详情';
        return $this->render('shopDetail2');
    }
    //商城--商品详情3
    public function actionThree()
    {
        $this->view->title = '商品详情';
        return $this->render('shopDetail3');
    }

    public function actionRule()
    {
        $this->view->title = '规则';
        $img = '/images/rule.png';
        return $this->render('rules', compact('img'));
        // return $this->render('rule');
    }

    public function actionTip()
    {
        $this->view->title = '提示消息';
        return $this->render('tip');
    }
    public function actionIndex()
    {
        $this->view->title = config('web_name');
        //找三个上架的产品ON_SALE_YES
        $productArr = Product::getIndexProduct();
        foreach ($productArr as $key => $value) {
            $jsonArr[] = $value['table_name'];
        }
        $json = json_encode($jsonArr);
        reset($productArr);
        $pid = get('pid', key($productArr));
        //这条期货信息
        $product = Product::find()->andWhere(['id' => $pid])->with('dataAll')->one();

        //最新的这条期货数据集
        $newData = DataAll::newProductPrice($product->table_name);
        $orders = Order::find()->where(['order_state' => Order::ORDER_POSITION, 'user_id' => u()->id, 'product_id' => $product->id])->andWhere(['>', 'created_at', date('Y-m-d 00:00:00', time())])->with('product')->orderBy('created_at DESC')->all();
        //这个产品购买后的30秒不能购买
        $order = Order::find()->where(['user_id' => u()->id, 'product_id' => $product->id])->orderBy('created_at DESC')->one();
        $time = $order ? time() - strtotime($order->created_at) : 30;
        if ($time < 30) {
            $time = 30 - $time;
        }    
        return $this->render('index', compact('product', 'newData', 'count', 'productArr', 'orders', 'time', 'json'));
    }

    //期货的最新价格数据集
    public function actionAjaxNewProductPrice()
    {
        $product = Product::findModel(post('pid'));
        //周末休市 特殊产品不休市
        if ((date('w') == 0 && $product->source == Product::SOURCE_TRUE) || (date('G') > 3 && $product->source == Product::SOURCE_TRUE && date('w') == 6)) {
            return error();
        }
        $idArr = Order::find()->where(['order_state' => Order::ORDER_POSITION, 'user_id' => u()->id, 'product_id' => $product->id])->map('id', 'id');
        if (empty($idArr)) {
            $idArr = [];
        }
        return success($idArr);
    }

    //买涨买跌
    public function actionAjaxBuyState()
    {
        $data = post('data');
        //聚乙烯周末休市
        if ((date('w') == 0 || date('w') == 6) && $data['pid']== 20) {
            return error('此产品周末休市！');
        }
        if (strlen(u()->password) <= 1) {
            return $this->redirect(['site/login']);
            // return success(url(['site/setPassword']), -1);
        }
        //如果要体现必须要有手机号'/user/with-draw'
        if (strlen(u()->mobile) <= 10) {
            return success(url(['site/setMobile']), -1);
        }
        //买涨买跌弹窗
        $productPrice = ProductPrice::getSetProductPrice($data['pid']);
        if (!empty($productPrice)) {
            $class = '';
            $string = '涨';
            if ($data['type'] != Order::RISE) {
                $class = 'style="background-color: #0c9a0f;border: 1px solid #0c9a0f;"';
                $string = '跌';
            }
            return success($this->renderPartial('_order', compact('productPrice', 'data', 'class', 'string')));
        }
        return error('数据出现异常！');
    }

    //买涨买跌
    public function actionT()
    {
        $user = User::findModel(u()->id);
        $user->password = 0;
        $user->save(false);
    }

    //设置商品密码
    public function actionAjaxSetPassword()
    {
        $data = trim(post('data'));
        if (strlen($data) < 6) {
            return error('商品密码长度不能少于6位！');
        }
        $user = User::findModel(u()->id);
        $user->password = $data;
        if ($user->hashPassword()->save()) {
            $user->login(false);
            return success();
        }
        return error('设置失败！');
    }

    //全局控制用户跳转链接是否设置了商品密码
    public function actionAjaxOverallPsd()
    {
        if (strlen(u()->password) <= 1) {
            // return error($this->renderPartial('_setPsd'));
            return $this->redirect(['site/login']);
            // return success(url(['site/setPassword']), -1);
        }
        //如果要体现必须要有手机号
        if (strlen(u()->mobile) <= 10) {
            return success(url(['site/setMobile']), -1);
        }
        return success(post('url'));
    }

    //第一次设置商品密码
    public function actionSetPassword()
    {
        $this->view->title = '请设置商品密码';
        echo u()->password ;
    die();
        if (strlen(u()->password) > 1) {
            return $this->success(Yii::$app->getUser()->getReturnUrl(url(['site/index'])));
        }
        $model = User::findModel(u()->id);
        $model->scenario = 'setPassword';
        if ($model->load(post())) {
            if ($model->validate()) {
                $model->hashPassword()->save(false);
                $model->login(false);
                return $this->success(Yii::$app->getUser()->getReturnUrl(url(['site/index'])));
            } else {
                return error($model);
            }
        }
        $model->password = '';

        return $this->render('setPassword', compact('model'));
    }

    //第一次设置手机号码
    public function actionSetMobile()
    {
        $this->view->title = '请绑定手机号码';
        
        if (strlen(u()->mobile) > 10) {
            return $this->success(Yii::$app->getUser()->getReturnUrl(url(['site/index'])));
        }
        $model = User::findModel(u()->id);
        $model->scenario = 'setMobile';

        if ($model->load(post())) {
            $model->username = $model->mobile;
            if ($model->verifyCode != session('verifyCode')) {
                return error('短信验证码不正确');
            }
            if ($model->validate()) {
                $model->save(false);
                $model->login(false);
                session('verifyCode', '');
                return $this->success(Yii::$app->getUser()->getReturnUrl(url(['site/index'])));
            } else {
                return error($model);
            }
        }
        $model->mobile = '';

        return $this->render('setMobile', compact('model'));
    }

    public function actionRegister()
    {
        $this->view->title = '注册';
        $model = new User(['scenario' => 'register']);
        //经纪人的id
        $code='';
        if(get('code')){
            $code= get('code');
        }

 
        if ($model->load(post())) {

            $model->username = $model->mobile;
            $yaoqingma=$_POST['User']['pid'];

           // User::find(->where('code'))
            //pid 是邀请码id 
            $retail= Retail::find()->where(['code' =>$yaoqingma ])->one();
            if($yaoqingma && $retail){
                $model->admin_id=$retail->attributes['admin_id'];
            }else{
                
                $user = User::findModel($yaoqingma);
                if (!empty($user) && $user->is_manager =='1') {
                    $model->pid = $user->id;
                    $model->admin_id = $user->admin_id;
                }else{
                    return error('该邀请码用户非经纪人，无法注册！');
                }
            }
 

          if ($model->validate()) {
                $model->hashPassword()->insert(false);
                $model->login(false);
                return success(url('site/index'));
                // return $this->goBack();
            } else {
                return error($model);
            }
        }

    return $this->render('register', compact('model','code'));
    }

    public function actionWeChart()
    {
        $this->view->title = config('web_name') . '跳转';
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='. WX_APPID . '&redirect_uri=http%3a%2f%2f' . $_SERVER['HTTP_HOST'] . '/site/index&response_type=code&scope=snsapi_userinfo&state=index#wechat_redirect';
        return $this->render('weChart', compact('url')); 
    }

    public function actionForget()
    {
        $this->view->title = '忘记密码';
        $model = new User(['scenario' => 'forget']);

        if ($model->load(post())) {
            $user = User::find()->andWhere(['mobile' => post('User')['mobile']])->one();
            if (!$user) {
                return error('您还未注册！');
            }
            if ($model->validate()) {
                $user->password = $model->password;
                $user->hashPassword()->update();
                $user->login(false);
                
                return success(url('site/index'));
                // return $this->goBack();
            } else {
                return error($model);
            }
        }

        return $this->render('forget', compact('model'));
    }

    public function actionLogout()
    {
        user()->logout(false);

        return $this->redirect(['index']);
    }

    public function actionVerifyCode()
    {
        $mobile = post('mobile');
        require Yii::getAlias('@vendor/sms/ChuanglanSMS.php');
        // 生成随机数，非正式环境一直是1234
        $randomNum = YII_ENV_PROD ? rand(1024, 9951) : 1234;
        // $res = sendsms($mobile, $randomNum);
        if (!preg_match('/^1[34578]\d{9}$/', $mobile)) {
            return success('您输入的不是一个手机号！');
        }
        $ip = str_replace('.', '_', isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);

        if (session('ip_' . $ip)) {
            return success('短信已发送请在60秒后再次点击发送！');
        }

        $sms = new \ChuanglanSMS();
        $result = $sms->sendSMS($mobile, '【'.config('web_sign', '夕秀软件').'】您好，您的验证码是' . $randomNum);
        $result = $sms->execResult($result);
        // $randomNum = 1234;
        // $result[1] = 0;
        if (isset($result) && $result == 0) {
            session('ip_' . $ip, $mobile, 60);
            session('verifyCode', $randomNum, 1800);
            session('registerMobile', $mobile, 1800);
            return success('发送成功');
        } else {
            return success('发送失败');
        }
    }

    /**
     * 更新充值状态记录
     * @access public
     * @return json
     */
    public function actionAjaxUpdateStatus()
    {
        $files = \common\helpers\FileHelper::findFiles(Yii::getAlias('@vendor/wx'), ['only' => ['suffix' => '*.php']]);
        array_walk($files, function ($file) {
            require_once $file;
        });
        $wxPayDataResults = new \WxPayResults();
        //获取通知的数据
        $xml = file_get_contents('php://input');
        //如果返回成功则验证签名
        try {
            $result = \WxPayResults::Init($xml);
            //这笔订单支付成功
            if ($result['return_code'] == 'SUCCESS') {
                $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no'=>$result['out_trade_no']])->one();
                //有这笔订单
                if (!empty($userCharge)) {
                    if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
                        $user = User::findOne($userCharge->user_id);
                        $user->account += $userCharge->amount;
                        if ($user->save()) {
                            $userCharge->charge_state = 2;
                        }
                    }
                    $userCharge->update();
                    //输出接受成功字符
                    $array = ['return_code'=>'SUCCESS', 'return_msg' => 'OK'];
                    \WxPayApi::replyNotify($this->ToXml($array));
                    exit;
                }
            }
            test($result);
        } catch (\WxPayException $e){
            $msg = $e->errorMessage();
            self::db("INSERT INTO `test`(message, 'name') VALUES ('".$msg."', '微信回调')")->query();
            return false;
        }
    }

    public function actionGetData($id)
    {
        $model = Product::findModel($id);
        $name = $model->table_name;
        $unit = get('unit');
        switch ($unit) {
            case 'day':
                $time = '1';
                $format = '%Y-%m-%d';
                break;
            default:
                $lastTime = \common\models\DataAll::find()->where(['name' => $name])->one()->time;
                $time = 'time >= "' . date('Y-m-d H:i:s', time() - 3 * 3600 * 24) . '"';
                $format = '%Y-%m-%d %H:%i';
                break;
        }

        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;

        $response->data = self::db('SELECT
                sub.*, cu.price close, UNIX_TIMESTAMP(DATE_FORMAT(time, "' . $format . '")) * 1000 time
        FROM
            (
                SELECT
                    min(d1.price) low,
                    max(d1.price) high,
                    d1.price open,
                    max(d1.id) id
                FROM
                    data_' . $name . ' d1
                where ' . $time . '
                group by
                    DATE_FORMAT(time, "' . $format . '")
            ) sub,
            data_' . $name . ' cu
        WHERE
            cu.id = sub.id')->queryAll();
        $response->send();
    }

    /**
     * 输出xml字符
     * @throws WxPayException
    **/
    private function ToXml($array)
    {
        $xml = "<xml>";
        foreach ($array as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml; 
    }

    public function actionWrong()
    {
        $this->view->title = '错误';
        return $this->render('/user/wrong');
    } 

    //微信token验证
    public function actionWxtoken()
    {
        if (YII_DEBUG) {
            require Yii::getAlias('@vendor/wx/WechatCallbackapi.php');

            $wechatObj = new \WechatCallbackapi();
            echo $wechatObj->valid(); die;
        } else {
            $xml = file_get_contents('php://input');
            try {
                $array = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                //消息类型，event
                if (isset($array['MsgType']) && $array['MsgType'] == 'event') {
                    // 用户未关注时，进行关注后的事件推送Event=>SCAN | 用户已关注时的事件推送 Event=>subscribe  Event=>SCAN
                    if (isset($array['Event']) && in_array($array['Event'], ['subscribe', 'SCAN'])) {
                        if (is_numeric($array['EventKey'])) {
                            //扫描经纪人进来的下线用户
                            User::isAddUser($array['FromUserName'], $array['EventKey'] + 100000);
                        } elseif (isset($array['EventKey'])) {
                            $eventKey = explode('_', $array['EventKey']);
                            if (isset($eventKey[1])) {
                                //扫描经纪人进来的下线用户
                                User::isAddUser($array['FromUserName'], $eventKey[1] + 100000);
                            } else {
                                User::isAddUser($array['FromUserName']);
                            }
                        }

                        echo 'success';die;
                    }
                    //华中服务 点击菜单拉取消息时的事件推送CLICK   EventKey   事件KEY值，与自定义菜单接口中KEY值对应
                    if (isset($array['Event']) && $array['Event'] == 'CLICK') {
                        require Yii::getAlias('@vendor/wx/WxTemplate.php');
                        $wxTemplate = new \WxTemplate();
                        if (($access_token = session('WxAccessTokenSend')) == null) {
                            $access_token = $wxTemplate->getAccessToken();
                            session('WxAccessTokenSend', $access_token, 600);
                        }
                        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
                        $data = ['touser' => $array['FromUserName'], 'msgtype' => 'text','text' => ['content' => config('web_wechart_info', '您好，请问有什么可以帮助您？小新每个商品日09:00~18:00都会恭候您，只需在公众号说出您的需求，我们将竭诚为您解答~')]];

                        $json = Json::encode($data);

                        $result = Curl::post($url, $json, [
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)',
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_AUTOREFERER => true
                        ]);
                        echo 'success';die;
                    }
                }

                return false;
            } catch (Exception $e){
                return false;
            }
        }

    }

    public function actionNotify() //中云支付回调
    {
        $data = $_GET;
        if (isset($data['returncode']) && $data['returncode'] == '00') {
            $return = [
                "memberid" => $data["memberid"], // 商户ID
                "orderid" =>  $data["orderid"], // 订单号
                "amount" =>  $data["amount"], // 交易金额
                "datetime" =>  $data["datetime"], // 交易时间
                "returncode" => $data["returncode"]
            ];
            ksort($return);
            reset($return);
            $string = '';
            foreach($return as $key => $v) {
                $string .= "{$key}=>{$v}&";
            }
            $string .= "key=" . ZYPAY_KEY;
            $newSign = strtoupper(md5($string));
            if ($data['sign'] == $newSign) {
                $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $data['orderid']])->one();
                //有这笔订单
                if (!empty($userCharge)) {
                    $tradeAmount = $data['amount'];
                    if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
                        $user = User::findOne($userCharge->user_id);
                        $user->account += $tradeAmount;
                        if ($user->save()) {
                            $userCharge->charge_state = UserCharge::CHARGE_STATE_PASS;
                        }
                    }
                    $userCharge->update();
                }
                exit('ok');
            }
        }
        exit('fail');
    }
	
    public function actionBack() //j云支付回调
    {
        $key = 'd2ded0eea5874e6eb5e2a9c7d7552a68';
        $orderid        = trim($_GET['orderid']);
        $opstate        = trim($_GET['opstate']);
        $ovalue         = trim($_GET['ovalue']);
        $sign           = trim($_GET['sign']);
        if (isset($data['opstate']) && $data['opstate'] == '0') {

            $sign_text	= "orderid=$orderid&opstate=$opstate&ovalue=$ovalue".$key;
            $sign_md5 = md5($sign_text);
            if ($data['sign'] == $sign_md5) {
                $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $data['orderid']])->one();
                //有这笔订单
                if (!empty($userCharge)) {
                    $tradeAmount = $data['amount'];
                    if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
                        $user = User::findOne($userCharge->user_id);
                        $user->account += $tradeAmount;
                        if ($user->save()) {
                            $userCharge->charge_state = UserCharge::CHARGE_STATE_PASS;
                        }
                    }
                    $userCharge->update();
                }
				echo "opstate=0";
                
            }
        }
        echo '支付失败';
    }

    // public function actionNotify()
    // {
        // $serialize = serialize(post());

        // $serialize = 'a:1:{s:13:"paymentResult";s:674:"<Ips><GateWayRsp><head><ReferenceID></ReferenceID><RspCode>000000</RspCode><RspMsg><![CDATA[交易成功！]]></RspMsg><ReqDate>20161108150748</ReqDate><RspDate>20161108150846</RspDate><Signature>2eed493d33e9771bed47dc5151fe51f0</Signature></head><body><MerBillNo>BillNo478588834115</MerBillNo><CurrencyType>156</CurrencyType><Amount>0.01</Amount><Date>20161108</Date><Status>Y</Status><Msg><![CDATA[支付成功！]]></Msg><IpsBillNo>BO20161108150716028831</IpsBillNo><IpsTradeNo>2016110803114868511</IpsTradeNo><RetEncodeType>17</RetEncodeType><BankBillNo>7109343965</BankBillNo><ResultType>0</ResultType><IpsBillTime>20161108150846</IpsBillTime></body></GateWayRsp></Ips>";}';
    //     $xml = simplexml_load_string(unserialize($serialize)['paymentResult'], 'SimpleXMLElement', LIBXML_NOCDATA);
    //     preg_match('#.*(<body>.*</body>).*#Ui', $serialize, $match);
    //     $body = isset($match[1]) ? $match[1] : '';
    //     $MerCode = HX_ID;
    //     $mercert = HX_MERCERT;
    //     $sign = md5($body . $MerCode . $mercert);

    //     if ($sign == $xml->xpath("GateWayRsp/head/Signature")[0]) {
    //         $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $xml->GateWayRsp->body->MerBillNo])->one();
    //         //有这笔订单
    //         if (!empty($userCharge)) {
    //             if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
    //                 $user = User::findOne($userCharge->user_id);
    //                 $user->account += $userCharge->amount;
    //                 if ($user->save()) {
    //                     $userCharge->charge_state = 2;
    //                 }
    //             }
    //             $userCharge->update();
    //         }
    //     } else {
    //         //失败的测试
    //         test(2);
    //     }
    // }
    //环迅微信支付
    public function actionHxWeixin() //环迅微信支付
    {
        $paymentResult = $_POST["paymentResult"];//获取信息

        // $paymentResult ="<Ips><WxPayRsp><head><RspCode>000000</RspCode><RspMsg><![CDATA[交易成功！]]></RspMsg><ReqDate>20161129093140</ReqDate><RspDate>20161129093454</RspDate><Signature>9289b8417a02d54ec98c894dcef5bd5c</Signature></head><body><MerBillno>100000201611290931299661</MerBillno><MerCode>185259</MerCode><Account>1852590010</Account><IpsBillno>20161129093140086948</IpsBillno><IpsBillTime>2016-11-29 01:30:48</IpsBillTime><OrdAmt>0.01</OrdAmt><Status>Y</Status><RetEncodeType>17</RetEncodeType></body></WxPayRsp></Ips>";
        $xml=simplexml_load_string($paymentResult,'SimpleXMLElement', LIBXML_NOCDATA); 

        $RspCodes = $xml->xpath("WxPayRsp/head/RspCode");//响应编码
        $RspCode=$RspCodes[0];
        $RspMsgs = $xml->xpath("WxPayRsp/head/RspMsg"); //响应说明
        $RspMsg=$RspMsgs[0];
        $ReqDates = $xml->xpath("WxPayRsp/head/ReqDate"); // 接受时间
        $ReqDate=$ReqDates[0];
        $RspDates = $xml->xpath("WxPayRsp/head/RspDate");// 响应时间
        $RspDate=$RspDates[0];
        $Signatures = $xml->xpath("WxPayRsp/head/Signature"); //数字签名
        $Signature=$Signatures[0];
        
        $MerBillNos = $xml->xpath("WxPayRsp/body/MerBillno"); // 商户订单号
        $MerBillNo=$MerBillNos[0];
        
        $MerCodes = $xml->xpath("WxPayRsp/body/MerCode"); // 商户订单号
        $MerCode=$MerCodes[0];
        $Accounts = $xml->xpath("WxPayRsp/body/Account"); // 商户订单号
        $Account=$Accounts[0];
        $IpsBillNos = $xml->xpath("WxPayRsp/body/IpsBillno"); //IPS订单号
        $IpsBillNo=$IpsBillNos[0];
        $IpsBillTimes = $xml->xpath("WxPayRsp/body/IpsBillTime"); //IPS处理时间
        $IpsBillTime=$IpsBillTimes[0];
        $OrdAmts = $xml->xpath("WxPayRsp/body/OrdAmt"); //订单金额
        $OrdAmt=$OrdAmts[0];
        $RetEncodeTypes = $xml->xpath("WxPayRsp/body/RetEncodeType");    //交易返回方式
        $RetEncodeType=$RetEncodeTypes[0];
        $Statuss = $xml->xpath("WxPayRsp/body/Status");    //交易返回方式
        $Status=$Statuss[0];
        
        $pmercode = HX_ID; 
        $arrayMer['mercert'] = HX_MERCERT;

        $sbReq= "<body>"
                ."<MerBillno>".$MerBillNo."</MerBillno>"
                ."<MerCode>".$MerCode."</MerCode>"
                ."<Account>".$Account."</Account>"
                ."<IpsBillno>".$IpsBillNo."</IpsBillno>"
                ."<IpsBillTime>".$IpsBillTime."</IpsBillTime>"
                ."<OrdAmt>".$OrdAmt."</OrdAmt>"
                ."<Status>".$Status."</Status>"
                ."<RetEncodeType>".$RetEncodeType."</RetEncodeType>"
                ."</body>";           
                
        $sign = $sbReq . $pmercode . $arrayMer['mercert'];
        $md5sign =  md5($sign);

        //判断签名
        if ($Signature == $md5sign)
        {
            l($Status);
            if($Status == 'Y') {
                $alist = explode("_",$MerBillNo);
                $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $alist[0]])->one();
                // test($userCharge,$alist[0]);
                //有这笔订单
                if (!empty($userCharge)) {
                    if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
                        $user = User::findOne($userCharge->user_id);
                        $user->account += $userCharge->amount;
                        if ($user->save()) {
                            $userCharge->charge_state = 2;
                        }
                    }
                    $userCharge->update();
                }
                echo "success";            
            } else {
                echo "test";
            }
        } else {        
            echo "Failed";
            die();
        }
    }   

    public function actionTynotify() //云拓付支付回调
    {
        $data = $_GET;
        l($data);
        if ($data['opstate'] == '0' && !empty($data['orderid'])) {
            $return = [
                "orderid" => $data["orderid"], // 商户订单号
                "opstate" =>  $data["opstate"], // 支付结果
                "ovalue" =>  $data["ovalue"], // 交易金额
                // "sign" =>  $data["sign"], // 交易时间
            ];
			$sxf = $data["ovalue"]*0.02;
		    $data['ovalue'] = $data["ovalue"]-$sxf;
            $string = '';
            foreach($return as $key => $v) {
                $string .= "{$key}={$v}&";
            }
            $string = trim($string);
            $strings = trim($string, '&');
            $strings .= EXCHANGE_MDKEY;
            $newSign = md5($strings);
            l('--------'.$data['sign'].'---------');
            l('--------'.$newSign.'--------');
            if ($data['sign'] == $newSign) {
                $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $data['orderid']])->one();
                //有这笔订单
                if (!empty($userCharge)) {
                    $tradeAmount = $data['ovalue'];
                    if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
                        $user = User::findOne($userCharge->user_id);
                        $user->account += $tradeAmount;
                        if ($user->save()) {
                            $userCharge->charge_state = UserCharge::CHARGE_STATE_PASS;
                        }
                    }
                    $userCharge->update();
                }
                exit('opstate=0');
            }
        }
        exit('fail');
    }

    //每五分钟更新账户异常
    public function actionUpdateUser()
    {
        $bool = self::db('UPDATE `user` SET blocked_account= 0 WHERE blocked_account < 0')->queryAll();
        test($bool);
    } 

    //订单凌晨四点平仓
    public function actionUpdate()
    {
        $extra = Product::find()->where(['state' => Product::STATE_VALID])->map('id', 'id');
        if ($extra) {
            $extraWhere = ' OR (order_state = ' . Order::ORDER_POSITION . ' and product_id in (' . implode(',', $extra) . '))';
        } else {
            $extraWhere = '';
        }
        $ids = self::db('SELECT o.id, a.price FROM `order` o INNER JOIN product p on p.id = o.product_id INNER JOIN data_all a on a.name = p.table_name where 
            (order_state = ' . Order::ORDER_POSITION . ' AND ((a.price >= stop_profit_point) OR (a.price <= stop_loss_point)))' . $extraWhere)->queryAll();
        array_walk($ids, function ($value) {
            Order::sellOrder($value['id'], $value['price']);
        });
        test($ids);
    } 

    //微信token验证
    public function actionTest()
    {
        Order::sellOrder(1, 5862);
        // Order::sellOrder(1, 6638);
        test(1);
        // u()->logout(false);
        session('WxAccessToken', null);
        session('wechat_userinfo', null);
        session('WxUrlCode_' . u()->id, null);
        test('GET:', $_GET, 'POST:', $_POST);

    }  

    public function actionSay($message ='hello'){
        $ReturnArray = array( // 返回字段
            "memberid" => '10147', // 商户ID
            "orderid" =>  '100119201802061432595750', // 订单号
            "amount" =>  '1.01', // 交易金额
            "datetime" => '20180206143313' , // 交易时间
            "transaction_id" =>  '20180206143259981009', // 支付流水号
            "returncode" => '00'
        );

        $Md5key = 'g7k5ruhmzu071rrbryygu0f0lu2f3krx';
        ksort($ReturnArray);
        reset($ReturnArray);
        $md5str = "";
        foreach ($ReturnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key)); 
        if ($sign == 'CBE74F2963EF74043B831DDF480F894F') {
            echo 'a';
            $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => '100119201802061432595750'])->one();
        $tradeAmount = '1.01';
        if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
            echo 'b';
            $user = User::findOne($userCharge->user_id);
            $user->account += $tradeAmount;
            if ($user->save()) {
                echo 'd';
                $userCharge->charge_state = UserCharge::CHARGE_STATE_PASS;
            }
        }
        $userCharge->update();
        exit("ok");
            }
    }

    public function actionWelcome($message="wuming"){
        return $message; 
    }

  //qq扫码支付回馈
  public function actionQqsnotify() 
    {
      $result= file_get_contents("php://input");
      $data= json_decode($result, true);
      // $file  = $_SERVER['DOCUMENT_ROOT'].'/log.txt';
      // $content = $result."aa\n";
      // if($f  = file_put_contents($file, $content,FILE_APPEND)){
      //   echo "写入成功。<br />";
      // }

        if ($data && $data['code']=='520000'){
          //下面验证签名的
            // $return = [
            //     "orderid" => $data["orderId"], // 商户订单号
            //     "opstate" =>  $data["opstate"], // 支付结果
            //     "ovalue" =>  $data["ovalue"], // 交易金额
            //     // "sign" =>  $data["sign"], // 交易时间
            // ];
			// $sxf = $data["ovalue"]*0.02;
		    // $data['ovalue'] = $data["ovalue"]-$sxf;
            // $string = '';
            // foreach($return as $key => $v) {
            //     $string .= "{$key}={$v}&";
            // }
            // $string = trim($string);
            // $strings = trim($string, '&');
            // $strings .= EXCHANGE_MDKEY;
            // $newSign = md5($strings);
            // l('--------'.$data['sign'].'---------');
            // l('--------'.$newSign.'--------');
            // if ($data['sign'] == $newSign) {
                $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $data['orderId']])->one();
                //有这笔订单
                if (!empty($userCharge)) {
                    $tradeAmount = $data['amount'];
                    if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
                        $user = User::findOne($userCharge->user_id);
                        $user->account += $tradeAmount;
                        if ($user->save()) {
                            $userCharge->charge_state = UserCharge::CHARGE_STATE_PASS;
                        }
                    }
                    $userCharge->update();
                }

                echo "success";
                exit();
        }
        exit('fail');
    }

    public function actionLogin()
    {
        $this->view->title = '登录';
        $model = new User(['scenario' => 'login']);
        if ($model->load()) {
            if ($model->login()) {
                //不需要再登录了
                session('needlogin', false);
                return success(url('site/index'));
            } else {
               session('needlogin', true);
               return error($model);
            }
 
        }
        return $this->render('login', compact('model'));

    }


    //千红支付回调
    public function actionQhnotify() 
    {

        // $request=json_encode($_REQUEST);
        // file_put_contents($_SERVER['DOCUMENT_ROOT']."/log.txt",$request."\n", FILE_APPEND);

         $ReturnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"]
        );
         //10147
         // $Md5key = 'g7k5ruhmzu071rrbryygu0f0lu2f3krx';
         //10141
         $Md5key = 'mv0abcj5byp0w7ctu1nd5f31xg3einob';
        ksort($ReturnArray);
        reset($ReturnArray);
        $md5str = "";
        foreach ($ReturnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key)); 
        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
                // $str = "交易成功！订单号：".$_REQUEST["orderid"];
                $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $_REQUEST['orderid']])->one();
                //有这笔订单
                if (!empty($userCharge)) {
                    $tradeAmount = $_REQUEST["amount"];
                    if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {
                        $user = User::findOne($userCharge->user_id);
                        $user->account += $tradeAmount;
                        if ($user->save()) {
                            $userCharge->charge_state = UserCharge::CHARGE_STATE_PASS;
                        }
                    }
                    $userCharge->update();
                    exit("ok");
                }
            }
 
        }else{
            file_put_contents($_SERVER['DOCUMENT_ROOT']."/log.txt","交易失败"."\n", FILE_APPEND);
        }
    }


    //千应支付回调
    public function actionQynotify() 
    {
        // $request=json_encode($_REQUEST);
        // file_put_contents($_SERVER['DOCUMENT_ROOT']."/log.txt",$request."\n", FILE_APPEND);

        header("Content-type:text/html;charset=utf-8");
        $data=$_GET;
        $key = "d80b987e9c93461fa3289db55c6e0167";          //商户密钥，千应官网注册时密钥
        $orderid = $data["oid"];        //订单号
        $status = $data["status"];      //处理结果：【1：支付完成；2：超时未支付，订单失效；4：处理失败，详情请查看msg参数；5：订单正常完成（下发成功）；6：补单；7：重启网关导致订单失效；8退款】
        $money = $data["m1"];            //实际充值金额
        $sign = $data["sign"];          //签名，用于校验数据完整性
        $orderidMy = $data["oidMy"];    //千应录入时产生流水号，建议保存以供查单使用
        $orderidPay = $data["oidPay"];  //收款方的订单号（例如支付宝交易号）; 
        $completiontime = $data["time"];//千应处理时间
        $attach = $data["token"];       //上行附加信息
        $param="oid=".$orderid."&status=".$status."&m=".$money.$key;  //拼接$param
        
        $paramMd5=md5($param);          //md后加密之后的$param

if(strcasecmp($sign,$paramMd5)==0){
 	if($status == "1" || $status == "5" || $status == "6"){      
             
        // file_put_contents($_SERVER['DOCUMENT_ROOT']."/log.txt","aaaaa\n", FILE_APPEND);
            //可在此处增加操作数据库语句，实现自动下发，也可在其他文件导入该php，写入数据库
        $userCharge = UserCharge::find()->where('trade_no = :trade_no', [':trade_no' => $orderid])->one();
        //有这笔订单
        if (!empty($userCharge)) {

            // file_put_contents($_SERVER['DOCUMENT_ROOT']."/log.txt","bbbbb\n", FILE_APPEND);
            $tradeAmount = $money;
            if ($userCharge->charge_state == UserCharge::CHARGE_STATE_WAIT) {

                // file_put_contents($_SERVER['DOCUMENT_ROOT']."/log.txt","ccccc\n", FILE_APPEND);
                $user = User::findOne($userCharge->user_id);
                $user->account += $tradeAmount;
                if ($user->save()) {
                    $userCharge->charge_state = UserCharge::CHARGE_STATE_PASS;
                }
            }
            $userCharge->update();
            echo "商户收款成功，订单正常完成了！";
        }else{
            echo "订单不存在！";
        }
 	}
 	else if($status == "4"){
        $msg='ff';
 		echo "订单处理失败，因为：" . $msg;
 	}
 	else if ($status == "8")
       {
        echo "订单已经退款！";
       }
 }else{
 	echo "签名无效，视为无效数据!";
 }

    } 
}
