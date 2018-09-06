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
                         Динамика позиций <?= Html::encode($engine['name_se']); ?> <?= Html::encode($engine['name_region']); ?>
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
               <?php /*$scolors = array();
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
          }*/ ?>
               <div class="col-xs-12">
                    <table cellspacing="0" width="100%" class="table-responsive">
                         <thead>
                         <tr>
                              <th scope="col">
                                   Запросы
                              </th>
                              <?php foreach ($dateArray as $value) { ?>
                                   <th scope="col">
                                        <?= Html::encode($value); ?>
                                   </th>
                              <?php } ?>
                              <th scope="col" style="z-index: 2;">
                                   Динамика
                              </th>
                         </tr>
                         </thead>
                         <tbody>
                         <?php foreach ($queryArray as $qkey => $qvalue) { ?>
                              <tr class="trPosition">
                                        <th data-label="Запросы"><span class="color-item"><?= Html::encode($qvalue['query']); ?></span></th>
                                        <?php foreach ($start_data as $skey => $pvalue) {
                                             if($skey == $qkey) {?>
                                             <th data-label="<?= Html::encode($dateArray[0]); ?>">
                                                  <?php foreach ($pvalue as $key => $cpvalue) { ?>
                                                  <span class="color-item">
                                                       <?=$cpvalue ?>
                                                  </span>
                                                  <?php } ?>
                                             </th>
                                        <?php }
                                        } ?>
                                        <?php foreach ($end_data as $skey => $pvalue) {
                                             if($skey == $qkey) {?>
                                             <th data-label="<?= Html::encode($dateArray[1]); ?>">
                                                  <?php foreach ($pvalue as $key => $cpvalue) { ?>
                                                  <span class="color-item">
                                                       <?=$cpvalue ?>
                                                  </span>
                                                  <?php } ?>
                                             </th>
                                        <?php }
                                        } ?>
                                        <?php foreach ($dinamics as $dkey => $pvalue) {
                                             if($dkey == $qkey) {?>
                                             <th style="position: relative;">
                                                  <i class="fa fa-arrow-circle-<? if( $pvalue > 0){ echo 'up'; }else if($pvalue < 0){echo 'down';}?>" aria-hidden="true" style="color: <? if( $pvalue > 0){ echo 'green'; }else if($pvalue < 0){echo 'red';} ?>; position: absolute; top: 13px;right: 20px;font-size: 20px!important;"></i>
                                                  <span class="color-item">
                                                       <?=$pvalue; ?>
                                                  </span>
                                             </th>
                                        <?php }
                                        } ?>
                              </tr>
                         <?php } ?>
                         </tbody>
                    </table>
               </div>
          </div>
     </div>
</section>
