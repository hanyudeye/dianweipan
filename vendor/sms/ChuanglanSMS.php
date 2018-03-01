<?php
header("Content-type:text/html; charset=UTF-8");

/* *
 * 类名：ChuanglanSmsApi
 * 功能：创蓝接口请求类
 * 详细：构造创蓝短信接口请求，获取远程HTTP数据
 * 版本：1.3
 * 日期：2016-07-16
 * 说明：
 * 以下代码只是为了方便客户测试而提供的样例代码，客户可以根据自己网站的需要，按照技术文档自行编写,并非一定要使用该代码。
 * 该代码仅供学习和研究创蓝接口使用，只是提供一个参考。
 */

class ChuanglanSMS {

	//创蓝发送短信接口URL, 如无必要，该参数可不用修改
	const API_SEND_URL='http://api.smsbao.com/sms';

	//创蓝短信余额查询接口URL, 如无必要，该参数可不用修改
	const API_BALANCE_QUERY_URL='http://sms.253.com/msg/balance';

	// const API_ACCOUNT='zhipin';//创蓝账号 替换成你自己的账号
	const API_ACCOUNT='quanminweitou';//创蓝账号 替换成你自己的账号

	// const API_PASSWORD='b3cf2d05e90983b262207655dd65dce7';//创蓝密码 替换成你自己的密码
    //帐号密码对应的md5加密
	const API_PASSWORD='e10adc3949ba59abbe56e057f20f883e';//创蓝密码 替换成你自己的密码

	/**
	 * 发送短信
	 *
	 * @param string $mobile 		手机号码
	 * @param string $msg 			短信内容
	 * @param string $needstatus 	是否需要状态报告
	 */
	public function sendSMS( $mobile, $msg, $needstatus = 1) {
		
		//创蓝接口参数
		$postArr = array (
				          'u' => self::API_ACCOUNT,
				          'p' => self::API_PASSWORD,
				          'c' => $msg,
				          'm' => $mobile,
                     );
		
		$result = $this->curlPost( self::API_SEND_URL , $postArr);
		return $result;
	}
	
	/**
	 * 查询额度
	 *
	 *  查询地址
	 */
	public function queryBalance() {
		
		//查询参数
		$postArr = array ( 
		          'un' => self::API_ACCOUNT,
		          'pw' => self::API_PASSWORD,
		);
		$result = $this->curlPost(self::API_BALANCE_QUERY_URL, $postArr);
		return $result;
	}

	/**
	 * 处理返回值
	 * 
	 */
	public function execResult($result){
		$result=$result;
		return $result;
	}

	/**
	 * 通过CURL发送HTTP请求
	 * @param string $url  //请求URL
	 * @param array $postFields //请求参数 
	 * @return mixed
	 */
	private function curlPost($url,$postFields){
		$postFields = http_build_query($postFields);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
		$result = curl_exec ( $ch );
		curl_close ( $ch );
		return $result;
	}
	
	//魔术获取
	public function __get($name){
		return $this->$name;
	}
	
	//魔术设置
	public function __set($name,$value){
		$this->$name=$value;
	}
}
?>