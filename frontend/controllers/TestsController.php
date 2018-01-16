<?php

namespace frontend\controllers;

use Yii;
use frontend\models\User;
use frontend\models\UserAccount;
use frontend\models\UserWithdraw;
use frontend\models\UserCharge;
use common\helpers\FileHelper;
use frontend\models\Product;
use frontend\models\Order;
use frontend\models\ProductPrice;
use frontend\models\BankCard;
use frontend\models\Coupon;
use frontend\models\UserCoupon;
use frontend\models\DataAll;

class TestsController extends \frontend\components\Controller
{
    public function beforeAction($action)
    {
        return true;
    }
 
    public function actionSay($message ='hello'){
        return $this->render('say',compact('message'));
    }

}
