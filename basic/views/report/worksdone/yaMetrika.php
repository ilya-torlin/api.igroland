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
<!--div class="col-xs-12"><h2 class="h2"><?= Html::encode($key_name); ?></h2></div-->
<div class="col-xs-12">
     <table cellspacing="0" width="100%" class="table-responsive">
          <thead>
          <tr>
               <?php foreach ($header as $value) { ?>
                    <th scope="col">
                         <?= Html::encode($value); ?>
                    </th>
               <?php } ?>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($table_value as $ya_value) { ?>
               <tr class="trPosition">
                    <?php $i=0; foreach ($ya_value as $pvalue) { ?>
                         <th data-label="<? if($i==0) { echo 'Месяц'; $i++; } else{ echo $header[$i++];  } ?>">
                                   <?= Html::encode($pvalue); ?>
                         </th>
                    <?php } ?>
               </tr>
          <?php } ?>
          </tbody>
     </table>
</div>
