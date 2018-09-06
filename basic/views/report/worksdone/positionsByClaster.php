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
          <div class="row" id="position-m-y">
               <div class="col-xs-10">
                    <h2 class="h2 menu-item" cont="<?= Html::encode($order+2); ?>">
                         Позиции за месяц по кластерам <?= Html::encode($engine['name_se']); ?>. <?= Html::encode($engine['name_region']); ?>
                    </h2>

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
               <div class="col-xs-12 js-collaps-all active">свернуть / развернуть все кластеры</div>
               <div class="col-xs-12">
                   <table cellspacing="0" width="100%" class="table-responsive  table-clasteк" >
                         <thead>
                         <tr>
                              <th scope="col">
                                   Кластер/Запрос
                              </th>
                              <?php foreach ($dateArray as $key => $value) { ?>
                                   <th scope="col" style="white-space: nowrap;">
                                        <?= Html::encode($value['date']); ?>
                                   </th>
                              <?php } ?>
                                    <th scope="col" style="z-index: 2;">
                                   Динамика
                              </th>
                         </tr>
                         </thead>
                      <?php
                      	foreach ($dateArray as  $pkey => $pvalue) {
	                      	$totalsum[$pkey] = 0;
	                        $totalcount[$pkey] = 0;
                        }
                      	foreach ($queryGroupArray as $qgroupkey => $qgroupvalue) { ?>
                                                <tbody class="klaster-h">
						<tr class="trPosition">
							<th data-label="Кластер/Запрос">
								<div class="btn-c-p">
									<button class="btn-plus active" data-sid="#tbody<?=$qgroupkey;?>" ><i class="fa fa-plus" aria-hidden="true"></i><i class="fa fa-minus" style="display: none;" aria-hidden="true"></i></button>
									<span class="color-item"><?=$qgroupvalue->group;?></span>
								</div>

							</th>
                                                         <?php
                                                         $firstVal = -1;
                                                         $lastVal = 0;
                                                         foreach ($dateArray as  $pkey => $pvalue) { ?>
                                                         <th data-label="<?= Html::encode($pvalue['date']); ?>">
                                                             <span class="color-item">
                                                            <?php
                                                            $count = $data[$qgroupkey]['idposition-'. $pvalue['id']]['toptenquerycount'];
                                                            $sum = $data[$qgroupkey]['idposition-'. $pvalue['id']]['querycount'];
															$totalsum[$pkey] += $sum;
                    										$totalcount[$pkey] += $count;
                                                            $val = round (($count/$sum)*100,0);
                                                            $lastVal = $val;
                                                            if ($firstVal == -1) $firstVal = $val;
                                                            echo $val;
                                                                    ?>%

                                                             </span></th>
                                                         <?php } ?>
							<?php

                                                         $up = 0;
                                                           if ($lastVal > $firstVal) $up =1;
                                                           $dinamicVal = abs($lastVal - $firstVal);
                                                        ?>
                                                        <th data-label="Динамика" style="position:relative;">
                                                            <?php if ($lastVal != $firstVal){ ?>
                                                            <span class="color-item <?php if ($up){echo 'green';} else {echo 'red';}?>-fa"><span class="p-txt"><?php echo $dinamicVal;?>%</span> <i class="fa fa-arrow-circle-<?php if ($up){echo 'up';} else {echo 'down';}?>" aria-hidden="true" style="color: <? if( $up){ echo 'green'; }else{echo 'red';} ?>; position: absolute; top: 13px;right: 20px;font-size: 20px!important;"></i></span>
                                                            <?php } else {?> 0% <?php } ?>
                                                        </th>
						</tr>
						</tbody>
                                                 <tbody class="claster-cont" id="tbody<?=$qgroupkey;?>">
                                                    <?php foreach ($qgroupvalue['queries'] as $qvalue) { ?>
                              <tr class="trPosition">
                                        <th class="klaster" data-label="Запросы"><span class="color-item"><?= Html::encode($qvalue['query']); ?></span></th>
                                   <?php
                                    $firstVal = -1;
                                    $lastVal = 0;
                                   foreach ($dateArray as  $pvalue) {
                                       ?>
                                        <th data-label="<?= Html::encode($pvalue['date']); ?>">
                                             <?php
                                             $pkey = $pvalue['id'];
                                             $qkey = $qvalue['id'];
                                            //var_dump($qkey);
                                             if (!isset($data[$qgroupkey][$qkey][$pkey])){
                                                 $data[$qgroupkey][$qkey][$pkey] = '100';
                                             }
                                              $val =  $data[$qgroupkey][$qkey][$pkey];
                                              if (!$val) {$val = 100;}
                                              $lastVal = intval($val);
                                              if ($firstVal == -1) $firstVal = intval($val);
                                             ?>
                                             <span class="color-item">
                                                  <?= Html::encode($val); ?>
                                             </span>
                                        </th>
                                   <?php }


                                                         $up = 0;
                                                           if ($lastVal < $firstVal) $up =1;
                                                           $dinamicVal = abs($lastVal - $firstVal);


                                   ?>

                                         <th data-label="Динамика" style="position:relative;">
                                                            <?php if ($lastVal != $firstVal){ ?>
                                                            <span class="color-item <?php if ($up){echo 'green';} else {echo 'red';}?>-fa"><span class="p-txt"><?php echo $dinamicVal;?></span> <i class="fa fa-arrow-circle-<?php if ($up){echo 'up';} else {echo 'down';}?>" aria-hidden="true" style="color: <? if( $up){ echo 'green'; }else{echo 'red';} ?>; position: absolute; top: 13px;right: 20px;font-size: 20px!important;"></i></span>
                                                            <?php } else {?> 0 <?php } ?>
                                                        </th>
                              </tr>
                         <?php } ?>

                                              </tbody>
                      <?php } ?>
						<thead class="klaster-h">
							<tr class="trPosition">
								<th data-label="Суммарно по всем кластерам (Топ-10)">
			                        <span class="color-item">Суммарно по всем кластерам (Топ-10)</span>
								</th>
								<?php
								$firstVal = -1;
                                 $lastVal = 0;
                            	foreach ($dateArray as  $pkey => $pvalue) {
                                   if($totalsum[$pkey] != 0){
                                        $val = round (($totalcount[$pkey]/$totalsum[$pkey])*100,0);
                                   }
                                   else{
                                        $val = 0;
                                   }

	                                $lastVal = $val;
                                    if ($firstVal == -1) $firstVal = $val;
	                            ?>
	                            <th data-label="<?php echo $val;  ?>">
	                        		<?php echo $val;  ?>
	                                %
	                            </th>
	                            <?php } ?>
	                            <th data-label="Динамика" style="position:relative;">
	                    			<?php
	                    			$up = 0;
                    				if ($lastVal > $firstVal) $up = 1;
                                    $dinamicVal = abs($lastVal - $firstVal);
	                    			?>
	                    			<?php if ($lastVal != $firstVal){ ?>
                                    <span class="color-item <?php if ($up){echo 'green';} else {echo 'red';}?>-fa"><span class="p-txt"><?php echo $dinamicVal;?>%</span> <i class="fa fa-arrow-circle-<?php if ($up){echo 'up';} else {echo 'down';}?>" aria-hidden="true" style="color: <? if( $up){ echo 'green'; }else{echo 'red';} ?>; position: absolute; top: 13px;right: 20px;font-size: 20px!important;"></i></span>
                                    <?php } else {?> 0% <?php } ?>
	                            </th>
							</tr>
						</thead>
                   </table>
               </div>
          </div>
     </div>
</section>
<script>

    $(function() {
    $(document).on( "click", "#position-<?= Html::encode($order+2); ?> .js-collaps-all", function(){
            this_parent = $(this).parent()[0];
            if(!$(this).hasClass('active')){
               $(this).addClass('active');
               $('#position-<?= Html::encode($order+2); ?> .claster-cont').hide();
               $(this).addClass('active');
               $(this_parent).find('.fa-plus').show();
               $(this_parent).find('.fa-minus').hide();
               $(this_parent).find('.klaster-h').removeClass('active');
            }else{
               $(this).removeClass('active');
               $('#position-<?= Html::encode($order+2); ?> .claster-cont').show();
               $(this_parent).find('.fa-plus').hide();
               $(this_parent).find('.fa-minus').show();
               $(this_parent).find('.klaster-h').addClass('active');
            }
    });
    $(document).on( "click", "#position-<?= Html::encode($order+2); ?> .btn-plus", function(){
        if(!$(this).hasClass('active')){
            $('#position-<?= Html::encode($order+2); ?> '+ $(this).data().sid).hide();
            $(this).addClass('active');
            $(this).find('.fa-plus').show();
            $(this).find('.fa-minus').hide();
            $(this).parents('.klaster-h').removeClass('active');
        }else{
            $('#position-<?= Html::encode($order+2); ?> '+$(this).data().sid).show();
            $(this).removeClass('active');
            $(this).find('.fa-plus').hide();
            $(this).find('.fa-minus').show();

            $(this).parents('.klaster-h').addClass('active');
        }
    });
});

</script>
