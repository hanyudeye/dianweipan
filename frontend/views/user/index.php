<?php $this->regCss('geren.css') ?>
<?php $this->regCss('jiaoyi.css') ?>
<?php //$this->regCss('bootstrap.min.css') ?>
<?php use frontend\models\User; ?>
<?php use yii\helpers\Url;  ?>
        <!--个人中心-->
        <div class="personal">
            <div class="per_top">
                <div class="boxflex">
    <div class="img-wrap"><img class="userimage" src="<?= config('web_logo') ?>"></div>
                    <div class="box_flex_1">
    <div class="p_zichan"> 资产：<span id="total-asset"><?= $user->account ?></span>元</div>
                    </div>
                    <div class="btncenter-withdraw-wrap">
                        <div class="recharge"><a class="overallPsd" data-url="<?= url(['user/recharge', 'user_id' => u()->id]) ?>">充值</a></div>
                        <div class="withdraw"><a class="overallPsd" data-url="<?= url(['user/withDraw']) ?>">提现</a></div>
                    </div>
                </div>
                <div class="boxflex cash-wrap">
                    <div class="box_flex_1">
                        <div class="p_zijin">
                            <div class="key" id="able-cash"><?= $user->account - $user->blocked_account ?></div>
                            <div class="value">可用资金</div>
                        </div>
                    </div>
                    <div class="box_flex_1">
                        <div class="p_zijin">
                            <div class="key" id="used-cash"><?= $user->blocked_account ?></div>
                            <div class="value">占用合约定金</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="center-list-wrap">
                <ul>
                    <li class="table" data-index="0">
                        <a href="<?= url(['user/transDetail']) ?>">
                        <i class="icon icon-operrec table-cell"></i>
                        <span class="table-cell title-text">我的商品轨迹</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>
                    </li>
<!--                     <li class="table" data-index="0">
                        <a href="<?= url(['order/position']) ?>">
                        <i class="icon icon-position table-cell"></i>
                        <span class="table-cell title-text">我的持仓单</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>
                    </li> -->
                    <li class="table" data-index="1">
                        <a href="<?= url(['user/insideMoney']) ?>">
                        <i class="icon icon-expenditure table-cell"></i>
                        <span class="table-cell title-text">出金记录</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>
                    </li>
                    <li class="table" data-index="1">
                        <a href="<?= url(['user/outMoney']) ?>">
                        <i class="icon icon-income table-cell"></i>
                        <span class="table-cell title-text">入金记录</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>
                    </li>
<!--                     <li class="table" data-index="1">
                        <a href="<?= url(['user/bankCard']) ?>">
                        <i class="icon icon-income table-cell"></i>
                        <span class="table-cell title-text">绑定银行卡</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>
                    </li> -->
                    <li class="table" data-index="3">
                        <a href="<?= url(['user/setting']) ?>">
                            <i class="icon icon-setting table-cell"></i>
                            <span class="table-cell title-text">个人设置</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>                        
                    </li>
					<li class="table" data-index="1">
                        <a href="<?= url(['manager/register']) ?>">
                            <i class="icon icon-setting table-cell"></i>
                            <span class="table-cell title-text">申请经纪人</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>                        
                    </li>
                    <?php if (u()->is_manager == User::IS_MANAGER_YES): ?>
                    <li class="table" data-index="1">
                        <a href="<?= url(['manager/index']) ?>">
                        <i class="icon icon-customer table-cell"></i>
                        <span class="table-cell title-text">经纪人</span>
                        </a>
                        <span class="earrow earrow-right table-cell"></span>
                    </li>
                    <?php endif ?>
                </ul>
            </div>
        </div>
        <div class="myContent" style=" text-align:center">

            <a href="<?= Url::to(['site/logout']); ?>" style="font-size: 1.2em;color:white;background: #e6262e;text-align: center ; width:250px; display:inline-block; height:38px; line-height:38px; overflow:hidden;border:1px solid #e6262e; margin:0 auto;border-radius: 20px;" class="btn btn-primary btn-lg active" role="button">退 出 登 陆</a>
        </div>

        