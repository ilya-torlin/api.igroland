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
<section class="feedback content" id="position-<?= Html::encode($order+2); ?>">
     <div class="container">
          <div class="row">
               <div class="col-xs-10">
                    <h2 class="h2 menu-item" cont="<?= Html::encode($order+2); ?>">
                         <?= Html::encode($name); ?>
                    </h2>
               </div>
               <div class="col-xs-2">
                    <h2 class="h2 btn-down">
                         <a href="javascript:void(0)" data-slide="#block<?= Html::encode($order); ?>">
                              <i class="fa fa-angle-up" aria-hidden="true"></i>
                         </a>
                    </h2>
               </div>
               <div class="col-xs-12 text-acounter" id="block<?= Html::encode($order); ?>">
                    <?php
                    foreach ($text as $txt) {
                          if($txt['type'] == 'textarea'){
                     
                          echo $txt['value']; 
                     }
                    } ?>

               </div>
          </div>
     </div>
</section>
