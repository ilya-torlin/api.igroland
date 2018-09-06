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
                                 <?php foreach($data as $ckey => $value) {?>
                                      {
                                           <?php
                                                if($ckey == 'Переходы из поисковых систем'){
                                                     $clr = '#fc4526';
                                                }
                                                else if($ckey == 'Прямые заходы'){
                                                     $clr = '#62ace6';
                                                }
                                                else if ($ckey == 'Внутренние переходы'){
                                                     $clr = '#9b40ab';
                                                }
                                                else if ($ckey == 'Переходы по ссылкам на сайтах'){
                                                     $clr = '#d79159';
                                                }
                                                else if ($ckey == 'Переходы из социальных сетей'){
                                                   $clr = '#8bc554';
                                                }
                                                else if($ckey == 'Переходы по рекламе'){
                                                     $clr = '#4c4c4c';
                                                }
                                           ?>
                                           label: "<?= Html::encode($ckey); ?>",
                                           fill: false,
                                           backgroundColor: "<?= Html::encode($clr); ?>",
                                           borderColor: "<?= Html::encode($clr); ?>",
                                           data: [
                                                <?php for ($i=0; $i < count($value); $i++) {
                                                    echo $value[$i][2].',';
                                                } ?>
                                           ]
                                      },
                                 <?php } ?>
                                 ],
                            labels: [<?php for ($i=0; $i < count($value); $i++) {
                                echo '"'.$value[$i][0].'"'.',';
                            } ?>    ]
                         };
                         initById('yaColeb<?= Html::encode($order); ?>', '100%', myChartLine, null, dataObj);
