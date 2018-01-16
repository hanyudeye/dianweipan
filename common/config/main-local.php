<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=100gift',
            // 'username' => '100gift',
            //数据库用户名密码/吴明
            'username' => 'root',
            'password' => 'wuming',
            'charset' => 'utf8',
            'tablePrefix' => ''
        ],
        'db-slave' => [
            'class' => 'yii\db\Connection',
            // 配置主服务器
            'masterConfig' => [
                'username' => '100gift',
                'password' => '',
                'attributes' => [
                    // use a smaller connection timeout
                    PDO::ATTR_TIMEOUT => 10,
                ],
            ],
            // 配置主服务器组
            'masters' => [
                [
                    'dsn' => 'mysql:host=localhost;dbname=dbname;charset=utf8',
                ],
                
            ],
            // 配置从服务器
            'slaveConfig' => [
                'username' => 'root',
                'password' => '',
                'attributes' => [
                    // use a smaller connection timeout
                    PDO::ATTR_TIMEOUT => 10,
                ],
            ],
            // 配置从服务器组
            'slaves' => [
                [
                    'dsn' => 'mysql:host=115.28.93.101;dbname=dbname;charset=utf8',
                ],
            ],
            'tablePrefix' => '',
        ],
        'redis' => [
            'class' => 'common\components\Redis',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.exmail.qq.com',
                'username' => '459967016@qq.com',
                'password' => '',
                'encryption' => 'tls',
            ],
            'messageConfig' => [
                'charset' => 'UTF-8',
                'from' => ['459967016@qq' => 'ChisWill']
            ],
        ],
    ],
];
