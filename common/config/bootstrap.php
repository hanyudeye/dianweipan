<?php
/**
 * 公共常量定义
 */
const PAGE_SIZE = 10;
const THEME_NAME = 'basic';
const SECRET_KEY = 'ChisWill';

const WEB_DOMAIN = 'http://royal.sqiesy.cn';

const WX_APPID = 'wxeaa5fbb6a4da4689';
const WX_MCHID = '1337714901';
const WX_KEY = 'VKcJg2LUnnRPjmYtPX3Tfm8vqradppF9';
const WX_APPSECRET = '0fb7fbe586ece988ce6f7507a8c5cf3a';
const WX_TOKEN = 'abcdef123456';

const HX_ID = '193439';
const HX_TID = '1934390012';
const HX_MERCERT = 'PODXWNx5N2HsgSTM6xOe9F7V5B3g04aC8gMPYfaTlzA0m0NZWoo0fgDczc0oYjFq6hhcabqxEoJesUcNnKTQxUD0QfXWlrRUuCQaDK9aqjxPFptsREBhk5eSv5N7vLTM';

const HX_PAY_DOMAIN = 'http://pay.mantingfen.cn';

//中云支付
const ZYPAY_ID = '12351';
const ZYPAY_KEY = 'IPwWl4zYS5d38ZRx1mZzM6wq7RTscO';

const ATTR_CREATED_AT = 'created_at';
const ATTR_CREATED_BY = 'created_by';
const ATTR_UPDATED_AT = 'updated_at';
const ATTR_UPDATED_BY = 'updated_by';
// 云托付
const EXCHANGE_ID = '1735';
const EXCHANGE_MDKEY = 'd2ded0eea5874e6eb5e2a9c7d7552a68';



/**
 * 路径别名定义
 */
Yii::setAlias('common', dirname(__DIR__));
Yii::setAlias('frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('console', dirname(dirname(__DIR__)) . '/console');
Yii::setAlias('api', dirname(dirname(__DIR__)) . '/api');
/**
 * 引入自定义函数
 */
$files = common\helpers\FileHelper::findFiles(Yii::getAlias('@common/functions'), ['only' => ['suffix' => '*.php']]);
array_walk($files, function ($file) {
    require $file;
});
/**
 * 公共变量定义
 */
common\traits\ChisWill::$date = date('Y-m-d');
common\traits\ChisWill::$time = date('Y-m-d H:i:s');
/**
 * 绑定验证前事件，为每个使用`file`验证规则的字段自动绑定上传组件
 */
common\components\Event::on('common\components\ARModel', common\components\ARModel::EVENT_BEFORE_VALIDATE, function ($event) {
    foreach ($event->sender->rules() as $rule) {
        if ($rule[1] === 'file') {
            $fieldArr = (array) $rule[0];
            foreach ($fieldArr as $field) {
                $event->sender->setUploadedFile($field);
            }
        }
    }
});
/**
 * 日志组件的全局默认配置
 */
Yii::$container->set('yii\log\FileTarget', [
    'logVars' => [],
    'maxLogFiles' => 5,
    'maxFileSize' => 1024 * 5,
    'prefix' => ['common\models\Log', 'formatPrefix']
]);
Yii::$container->set('yii\log\DbTarget', [
    'logVars' => [],
    'prefix' => ['common\models\Log', 'formatPrefix']
]);

        
        