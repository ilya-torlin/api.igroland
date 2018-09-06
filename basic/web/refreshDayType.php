<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
$config = require(__DIR__ . '/../config/web.php');
new yii\web\Application($config);
$today = time();

$deleteDate = date('Y-m-d', $today);
\app\models\BathTarif::deleteAll(['<', 'date', $deleteDate]);
\app\models\BathTarifFuture::deleteAll(['<', 'date', $deleteDate]);
//Заполняем на будующее future days
$days = array();
$holidays = array('31.12','01.01','07.01','08.03','23.02','12.06','01.05','09.05','04.11');
for ($i = 1; $i <= 365; $i++) {
    $bathTarifFuture = \app\models\BathTarifFuture::find()->where(['date' =>date('Y-m-d', $today + 24 * 60 * 60 * $i)])->one();
    if (!$bathTarifFuture){    
    $day = date('N', $today + 24 * 60 * 60 * $i);
    $dayType = 1;
    if ($day >= 6) $dayType = 3; 
    
    if (in_array(date('d.m', $today + 24 * 60 * 60 * $i), $holidays))
            $dayType = 2;
    
     $bathTarifFuture = new \app\models\BathTarifFuture;
     $bathTarifFuture->date = date('Y-m-d', $today + 24 * 60 * 60 * $i);
     $bathTarifFuture->tariff_id = $dayType;
     $bathTarifFuture->save();
    }
     $days[$bathTarifFuture->date] = $bathTarifFuture->tariff_id;
}

$days = array();
for ($i = 1; $i <= 30; $i++) {
    //TODO надо еще и праздники обрабатывать
    $day = date('N', $today + 24 * 60 * 60 * $i);
    $dayType = 1;
    if ($day >= 6)
        $dayType = 3;
    $days[date('Y-m-d', $today + 24 * 60 * 60 * $i)] = $dayType;
}

$bathhouses = \app\models\Bathhouse::find()->with('bathTarifs')->all();
foreach ($bathhouses as $bath) {
    $daysCopy = $days;
    foreach ($bath->bathTarifs as $tarif) {
        if (array_key_exists($tarif->date, $daysCopy))
            unset($daysCopy[$tarif->date]);
    }

    foreach ($daysCopy as $day => $tarifType) {
        $tarif = new \app\models\BathTarif;
        $tarif->date = $day;
        $tarif->bathhouse_id = $bath->id;
        $tarif->tariff_id = $tarifType;
        $tarif->save();
    }
}
         
         
         