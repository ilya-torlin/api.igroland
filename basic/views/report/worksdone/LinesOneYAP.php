<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
?>
 dataObj = {
   datasets: [

             {
                  label: "Визиты",
                  fill: false,
                  backgroundColor: "#fc4526",
                  borderColor: "#fc4526",
                  data: [
                  <?php foreach($data as $value) {  echo $value[1].','; } ?>
                  ]
             },
             {
                  label: "Посетители",
                  fill: false,
                  backgroundColor: "#62ace6",
                  borderColor: "#62ace6",
                  data: [
                  <?php foreach($data as $value) {  echo $value[2].','; } ?>
                  ]
             },

        ],
   labels: [<?php foreach($data as $value) {  echo '"'.$value[0].'"'.','; } ?>]
};
initById('yaColeb<?= Html::encode(++$order); ?>', '100%', myChartLine, null, dataObj);
