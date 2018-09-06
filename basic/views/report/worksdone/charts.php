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
<section class="content position-city" id="position-<?=$order+2; ?>">
     <div class="container">
          <div class="row" id="position-t">
               <div class="col-xs-10">
                    <h2 class="h2 menu-item" cont="<?= Html::encode($order+2); ?>">
                         <?= Html::encode($name); ?>
                    </h2>
               </div>
               <div class="col-xs-2">
                    <h2 class="h2 btn-down">
                         <a href="javascript:void(0)" data-slide="#chart<?= Html::encode($order); ?>">
                              <i class="fa fa-angle-up" aria-hidden="true"></i>
                         </a>
                    </h2>
               </div>
          </div>
          <div class="row chart-cont" id="chart<?= Html::encode($order); ?>">
               <?php foreach($data as $key => $eng) { ?>
               <div class="col-xs-12 col-md-6" >
                    <h2 class="h2">
                         <?= Html::encode($key); ?>
                    </h2>
                    <canvas class="chart" id="myChart<?= Html::encode($key); ?>" ></canvas>
               </div>
               <?php } ?>
          </div>
     </div>
</section>
<script type="text/javascript">
     function myChartPie(dataArr, selector) {
            var ctx = document.getElementById(selector).getContext('2d');
            var chart = new Chart(ctx, {
                // The type of chart we want to create
                type: 'pie',

                // The data for our dataset
                data: {
                    datasets: [{
                        data: dataArr,
                        backgroundColor: ["#16A765", "#ffcc00", "#ff9b00", "#c9302c"],
                        label: 'Dataset 1'
                    }],
                    labels: [
                         <?php
                        // var_dump($color);
                         foreach ($color as $col) {
                              echo '"ТОП '.$col.'",';
                         }?>
                    ]
                },
                options: {
                    responsive: true,
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var allData = data.datasets[tooltipItem.datasetIndex].data;
                                var tooltipLabel = data.labels[tooltipItem.index];
                                var tooltipData = allData[tooltipItem.index];
                                var total = 0;
                                for (var i in allData) {
                                    total += allData[i];
                                }
                                var tooltipPercentage = Math.round((tooltipData / total) * 100);
                               // return   'Ср. знач. '+tooltipLabel +' - ' + tooltipPercentage+ '% ('+tooltipData+')';
                                return   'Ср. знач. '+tooltipLabel +' - ' + tooltipPercentage+ '%';
                            }
                        }
                    }

                }
            });
     }


     function initCharts(){
          <?php foreach($data as $key => $engine) { ?>
               initById('myChart<?= Html::encode($key); ?>', '90%', myChartPie, [
                    <?php foreach($engine as $val){
                         echo $val.',';
                    }?>
               ]);
          <?php } ?>
     }
</script>
