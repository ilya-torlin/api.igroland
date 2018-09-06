<?php

// NOTE: Make sure this file is not accessible when deployed to production

//defined('YII_DEBUG') or define('YII_DEBUG', true);
//defined('YII_ENV') or define('YII_ENV', 'test');


require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
$config = require(__DIR__ . '/../config/web.php');

new yii\web\Application($config);



$file = __DIR__ . '/bani.xml';
$xml = simplexml_load_file($file);
 
 echo "<pre>";
 $first = true;
 $rowArr = array();
 foreach ($xml->Worksheet->Table->Row as $row) {
     if ($first){ $first = false; continue;}
     $rowElem = array();
     foreach ($row->Cell as $val) {
     $rowElem[] = (string)$val->Data;
     }
     
     echo $rowElem[0];
     $rowArr[] = $rowElem;
     
     $bath = new app\models\Bathhouse();
$bath->address = $rowElem[11].' '.$rowElem[12];
$bath->email = $rowElem[15];
$bath->site = $rowElem[16];
$bath->tax = 15;
$bath->latitude = $rowElem[8];
$bath->longitude = $rowElem[7];
$bath->title = $rowElem[3];
$bath->description = $rowElem[3];
$bath->phone = $rowElem[14];
$bath->save();
echo '!!'.$bath->id.'!!<br>';
   
     
     
    
    }
    
   
 echo "</pre>";
    die();
  
 
 


