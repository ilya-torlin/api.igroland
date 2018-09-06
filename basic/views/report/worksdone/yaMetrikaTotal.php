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
<div class="col-xs-12"><h2 class="h2"><?= Html::encode($title); ?></h2></div>
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
          <?php foreach ($table_th as $col) { ?>
               <tr class="trPosition">
                    <th data-label="Показатель">
                              <?= Html::encode($col); ?>
                    </th>
                    <?php $i=0;  foreach ($total_array as $ya_value) { ?>
                         <th data-label="<?= Html::encode($header[++$i]); ?>">
                                   <?= Html::encode($ya_value[$col]); ?>
                         </th>
                    <?php } ?>
               </tr>
          <?php } ?>
          </tbody>
     </table>
</div>
