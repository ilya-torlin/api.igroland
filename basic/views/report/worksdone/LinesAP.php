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
                                 <?php foreach($colors as $ckey => $color) {?>
                                      {
                                           <?php
                                                if($ckey == 'red'){
                                                     $clr = '#c9302c';
                                                }
                                                else if($ckey == 'green'){
                                                     $clr = '#16a765';
                                                }
                                                else if ($ckey == 'yel'){
                                                     $clr = '#fc0';
                                                }
                                                else if ($ckey == 'orange'){
                                                     $clr = '#ff9b00';
                                                }
                                           ?>
                                           label: "Топ <?= Html::encode($color); ?>",
                                           fill: false,
                                           backgroundColor: "<?= Html::encode($clr); ?>",
                                           borderColor: "<?= Html::encode($clr); ?>",
                                           data: [
                                                <?php foreach($dateArray as $key => $val){
                                                    if (isset($data[$key][$ckey]))
                                                     echo $data[$key][$ckey].',';
                                                    else echo '0,';
                                                }?>
                                           ]
                                      },
                                 <?php } ?>
                                 ],
                            labels: [<?php foreach($dateArray as $val){
                                 echo '"'.$val['date'].'",';
                                        }?>    ]
                         };
                         initById('graphColeb<?= Html::encode($order); ?>', '100%', myChartLine, null, dataObj);
