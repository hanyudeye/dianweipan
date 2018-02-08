<?php $this->regCss('iconfont/iconfont.css') ?>
<?php $this->regCss('mine.css') ?>
<?php $this->regCss('common.css') ?>
<style type="text/css">
 body{background:#fff;}


	a{text-decoration: none;}
	img{max-width: 100%; height: auto;}
	.weixin-tip{display: none; position: fixed; left:0; top:0; bottom:0; background: rgba(0,0,0,0.8); filter:alpha(opacity=80);  height: 100%; width: 100%; z-index: 100;}
	.weixin-tip p{text-align: center; margin-top: 10%; padding:0 5%;}
	


</style>
	<div class="weixin-tip">
		<p>
			<img src="/images/live_weixin.png" alt="微信打开"/>
		</p>
	</div>

<div class="container " style="padding:0;">
    <p class="selecthe">选择充值面额（元）</p>
    <?php $form = self::beginForm(['showLabel' => false, 'action' => url(['user/pay']), 'id' => 'payform']) ?>
    <div class="boxflex1 paystyle" style="padding: 10px 15px 0;">
        <div class="group_btn clearfloat">
            <div class="btn_re">
                <a class="btn_money on">5000</a>
            </div>
 
            <div class="btn_re btn_center">
                <a class="btn_money">3000</a>
            </div>
            <!-- <div class="btn_re btn_center"> -->
            <!-- <a class="btn_money">2000</a> -->
            <!-- </div> -->
            <div class="btn_re btn_center">
                <a class="btn_money">1000</a>
            </div>
            <div class="btn_re">
                <a class="btn_money">500</a>
            </div>
            <div class="btn_re">
                <a class="btn_money">300</a>
            </div>
            <div class="btn_re">
                <a class="btn_money">100</a>
            </div>
        </div>
        <input type="hidden" id="amount" name="amount" value="3000">
        <input type="hidden" id="type" name="type" value="qqs">
        <!-- <input type="hidden" id="type" name="type" value="wxguma"> -->
    </div>
    <div class="boxflex1">
        <div class="moneyhead">充值方式</div>
    </div>
      <div class="boxflex1 paystyle checkImgqqs paytype" style="border-top:0;" value="qqs">
      <img src="/images/mobileqq.png" style="width: 20px;">
      <span>QQ钱包支付</span>
      <img src="/images/seleted.png" alt="" style="float:right;" class="check-pay" >
      </div>
 
    <div class="boxflex1 paystyle checkImgwx paytype" value="wx">
        <img src="/images/icon-chat.png" style="width: 20px;">
        <span>微信支付</span>
        <img src="/images/notseleted.png" alt="" style="float:right;" class="check-pay" >
      </div>
      <div class="boxflex1 paystyle checkImgkj paytype" value="kj">
      <img src="/images/pay.png" style="width: 20px;">
      <span>快捷支付</span>
      <img src="/images/notseleted.png" alt="" style="float:right;" class="check-pay" >
      </div>
    <div class="boxflex1 paystyle checkImgzfb paytype" value="wykj">
        <img src="/images/pay.png" style="width: 20px;">
        <span>网银快捷</span>
        <img src="/images/notseleted.png" alt="" style="float:right;" class="check-pay" >
    </div>
      <!-- <div class="boxflex1 paystyle checkImgzfb paytype" value="zfbguma"> -->
      <!-- <img src="/images/alipay.png" style="width: 20px;"> -->
      <!-- <span>支付宝支付</span> -->
      <!-- <img src="/images/notseleted.png" alt="" style="float:right;" class="check-pay" > -->
      <!-- </div> -->
   <div class="recharge-btn" id="payBtn">立即充值</div>

    <?php self::endForm() ?>
    <div class="row">
        <!-- <div class="col-xs-12 text-center font_14 remain">跳转至微信安全支付网页，微信转账说明</div> -->
<!--         <div class="col-xs-12 text-center font_12">
            <font>注1：暂时只能使用借记卡充值</font>
            <br>
            <font>注2：为了管控资金风险，单日充值限额20000元</font>
        </div> -->
    </div>
</div>
<script>
$(function() {
    $(".btn_money").click(function() {
        $(".on").removeClass("on");
        $(this).addClass("on");
        $('#amount').val($(this).html());
    });

    $('#payBtn').on('click', function(){
        var amount = $('#amount').val();
        if(!amount || isNaN(amount) || amount <= 0){
            alert('金额输入不合法!');
            return false;
        }
        $("#payform").submit();
    });

    $(".paytype").click(function(){
        $('#type').val($(this).attr("value"));
    $(".paytype").each(function(){
    $(this).find('.check-pay').attr({
    "src":"/images/notseleted.png"
    })
    });
    $(this).find('.check-pay').attr({
    "src":"/images/seleted.png"
    })
    })


	   var winHeight = $(window).height();
			function is_weixin() {
			    var ua = navigator.userAgent.toLowerCase();
			    if (ua.match(/MicroMessenger/i) == "micromessenger") {
			        return true;
			    } else {
			        return false;
			    }
			}
			var isWeixin = is_weixin();
			if(isWeixin){
				//$(".weixin-tip").css("height",winHeight);
	       //     $(".weixin-tip").show();
			}else{
          // document.location.href="http://syooau.cn/";
      }

})
</script>

        
