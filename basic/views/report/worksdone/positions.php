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
<section class="content position-city" id="position-<?= Html::encode($order+2); ?>">
     <div class="container">
          <!--<div class="row">-->
          <!--<div class="col-xs-10 col-md-10">-->
          <!--<h1 class="h1">Позиции за месяц</h1>-->
          <!--</div>-->
          <!--</div> тут пока с настройками оставим-->
          <div class="row" id="position-m-y">
               <div class="col-xs-10">
                    <!--h2 class="h2 menu-item" cont="2">
                         Позиции за месяц <span class="yandex-logo">Яндекс</span>.Пермь
                    </h2-->
                    <h2 class="h2 menu-item" cont="<?= Html::encode($order+2); ?>">
                         Позиции за месяц <?= Html::encode($engine['name_se']); ?>. <?= Html::encode($engine['name_region']); ?>
                    </h2>
                    <!--h2 class="h2 menu-item" cont="3">
                         Позиции за месяц <span class="google-logo">G</span><span class="google-logo">o</span><span class="google-logo">o</span><span class="google-logo">g</span><span class="google-logo">l</span><span class="google-logo">e</span>.Пермь
                    </h2-->
               </div>
               <div class="col-xs-2">
                    <h2 class="h2 btn-down">
                         <a href="javascript:void(0)" data-slide="#block<?= Html::encode($order); ?>">
                              <i class="fa fa-angle-up" aria-hidden="true"></i>
                         </a>
                    </h2>
               </div>
          </div>
          <div class="row" id="block<?= Html::encode($order); ?>">
               <?php $scolors = array();
                foreach ($colors as $color) {
                     if($color['type'] === 'text'){
                ?>
                    <div class="color-block-cont col-xs-12 col-sm-3 col-md-3">
                         <div class="color-block <?= Html::encode($color['name']); ?>">
                         <?php
                              $scolors[$color['name']] = $color['value']
                         ?>
                         </div>
                         <span class="color-discr">до <?= Html::encode($color['value']); ?> позиций</span>
                    </div>
               <?php }
               } ?>
               <div class="col-xs-12">
                    <table cellspacing="0" width="100%" class="table-responsive">
                         <thead>
                         <tr>
                              <th scope="col">
                                   Запросы
                              </th>
                              <?php foreach ($dateArray as $key => $value) { ?>
                                   <th scope="col">
                                        <?= Html::encode($value['date']); ?>
                                   </th>
                              <?php } ?>
                         </tr>
                         </thead>
                         <tbody>
                         <?php foreach ($queryArray as $qkey => $qvalue) { ?>
                              <tr class="trPosition">
                                        <th data-label="Запросы"><span class="color-item"><?= Html::encode($qvalue['query']); ?></span></th>
                                   <?php foreach ($dateArray as  $pvalue) { ?>
                                        <th data-label="<?= Html::encode($pvalue['date']); ?>">
                                             <?php
                                             $pkey = $pvalue['id'];
                                             //\Yii::trace($data[$qkey][$pkey]);
                                             if (!isset($data[$qkey][$pkey])){
                                                 //\Yii::trace($qkey);
                                                 $data[$qkey][$pkey] = '0';
                                             }
                                                  if($data[$qkey][$pkey] == 0){
                                                        $color = 'red';
                                                  }
                                                  else if($data[$qkey][$pkey] <= $scolors['green']){
                                                        $color = 'green';
                                                  }
                                                  else if ($data[$qkey][$pkey] <= $scolors['yel']){
                                                       $color = 'yel';
                                                  }
                                                  else if ($data[$qkey][$pkey] <= $scolors['orange']){
                                                        $color = 'orange';
                                                  }
                                                  else $color = 'red';
                                             ?>
                                             <span class="color-item <?= Html::encode($color); ?>">
                                                  <?= $data[$qkey][$pkey]==0?'-':$data[$qkey][$pkey]; ?>
                                             </span>
                                        </th>
                                   <?php } ?>
                              </tr>
                         <?php } ?>
                         </tbody>
                    </table>
               </div>
          </div>
     </div>
</section>
