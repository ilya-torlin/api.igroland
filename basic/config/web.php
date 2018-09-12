<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'debug'],
    'components' => [
        'searchHelper' => [
            'class' => 'app\components\SearchHelper',
        ],
        'htmlHelper' => [
            'class' => 'app\components\HTMLHelper',
        ],
        'allPositionsHelper' => [
            'class' => 'app\components\AllPositionsHelper',
        ],
        'metrikaHelper' => [
            'class' => 'app\components\MetrikaHelper',
        ],
         'permissionHelper' => [
            'class' => 'app\components\PermissionHelper',
        ],
        'smsSender' => [
            'class' => 'app\components\SmsSender',
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'j1e9GfeXI3ZQYHZAGybohyxY5pGJDG8_',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.yandex.ru', // e.g. smtp.mandrillapp.com or smtp.gmail.com
                'username' => 'system@praweb.ru',
                'password' => 'EqHgH938Xa',
                'port' => '465', // Port 25 is a very common port too
                'encryption' => 'ssl', // It is often used, check your provider or mail server specs
            ],
            'useFileTransport' => false,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'user',
                    'pluralize' => false,
                    'patterns' => [
                        'PUT,PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        'GET me' => 'me',
                        'GET search' => 'search',
                        'GET,HEAD <id>' => 'view',                        
                        'POST' => 'save',
                        'POST <id>/setonoff' => 'setonoff',
                        'GET,HEAD' => 'index',
                         '<id>/setonoff' => 'options',
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],
                 ['class' => 'yii\rest\UrlRule',
                    'controller' => 'category',
                    'pluralize' => false,
                    'patterns' => [  
                        'GET search' => 'search',
                        'GET,HEAD <id>' => 'view',                           
                        'GET,HEAD' => 'index',
                        'DELETE <id>' => 'delete',                                                 
                        'POST' => 'create',
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'categoryattach',
                    'pluralize' => false,
                    'patterns' => [  
                        'DELETE <id>' => 'delete',                                                 
                        'POST' => 'create',
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'productattach',
                    'pluralize' => false,
                    'patterns' => [  
                        'DELETE <id>' => 'delete',                                                 
                        'POST' => 'create',
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],
                
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'catalog',
                    'pluralize' => false,
                    'patterns' => [  
                        'PUT,PATCH <id>' => 'update',
                        'GET my' => 'getmy',                        
                        'GET,HEAD <id>' => 'view',                        
                        'GET,HEAD' => 'index',
                        'POST <id>/setonoff' => 'setonoff',
                        'POST' => 'create',
                        'DELETE <id>' => 'delete', 
                        '<id>/setonoff' => 'options',
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],
                 ['class' => 'yii\rest\UrlRule',
                    'controller' => 'export',
                    'pluralize' => false,
                    'patterns' => [
                        'PUT,PATCH <id>' => 'update',
                        'GET,HEAD <id>/view' => 'viewexport',
                        'GET,HEAD <link>' => 'view',
                        'GET,HEAD' => 'index',
                        'POST search' => 'search',
                        'POST' => 'add',
                        'POST <id>/setonoff' => 'setonoff',
                        'DELETE <id>' => 'delete',
                        '<link>' => 'options',
                        '<id>/setonoff' => 'options',
                         'search/<text>' => 'options',
                        '' => 'options',
                    ],
                ],
                 ['class' => 'yii\rest\UrlRule',
                    'controller' => 'product',
                    'pluralize' => false,
                    'patterns' => [  
                        'GET search' => 'search', 
                        'GET,HEAD <id>' => 'view',                        
                        'GET,HEAD' => 'index',
                        'POST <id>/setalternativetitle' => 'setalternativetitle',
                        'POST <id>/setuseadmingallery' => 'setuseadmingallery',
                        'POST <id>/settrademarkup' => 'settrademarkup',
                        'POST <id>/addgallery' => 'addgallery',
                        '<id>/addgallery' => 'options',
                        '<id>/setalternativetitle' => 'options',
                        '<id>/setuseadmingallery' => 'options',
                        '<id>/settrademarkup' => 'options',
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'supplier',
                    'pluralize' => false,
                    'patterns' => [  
                        'GET,HEAD' => 'index',
                        'PUT,PATCH <id>' => 'update',
                        'POST <id>/setonoff' => 'setonoff',
                        '<id>' => 'options',
                        '<id>/setonoff' => 'options',
                        '' => 'options',
                    ],
                ],
             
                ['class' => 'yii\rest\UrlRule',
                    'controller' => ['userrole' => 'userrole'],
                ],
                
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'file',
                    'pluralize' => false,
                    'patterns' => [
                        'PUT,PATCH getbyid' => 'getbyid',
                        'DELETE <id>' => 'delete',
                        'GET,HEAD <id>' => 'view',
                        'POST' => 'save',
                        //'POST <id>' => 'saveimage',
                        'GET,HEAD' => 'index',
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],                
                ['class' => 'yii\rest\UrlRule',
                    'controller' => 'login',
                    'pluralize' => false,
                    'patterns' => [
                        'POST' => 'login',
                        'POST logout' => 'logout' ,
                        'POST restore' => 'restore' ,
                        'POST registration' => 'registration' ,
                        'GET changepass' => 'changepass' ,
                        '<id>' => 'options',
                        '' => 'options',
                    ],
                ],    
                'test' => 'test',
            ],
        ],
    ],
    'params' => $params,
];


if (YII_ENV_DEV ) {
    // configuration adjustments for 'dev' environment

    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['92.53.107.26', '::1'],
        'panels' => [
            'db' => [
                'class' => 'yii\debug\panels\DbPanel',
                'defaultOrder' => [
                    'seq' => SORT_ASC
                ],
                'defaultFilter' => [
                    'type' => 'SELECT'
                ]
            ],
        ],
    ];




    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['92.53.107.26', '::1'],
            // 'allowedIPs' => ['*'],
    ];
}

return $config;
