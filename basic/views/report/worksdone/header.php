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
<!DOCTYPE html>
<html lang="ru">

<head>

	<meta charset="utf-8">

	<title>Отчет | <?= Html::encode($project['name']); ?> | <?= Html::encode($date); ?></title>
	<meta name="description" content="">

	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<meta property="og:image" content="/assets/report/img/favicon/mstile-150x150.png">
	<link rel="shortcut icon" href="/assets/report/img/favicon/favicon.ico" type="image/x-icon">
	<link rel="apple-touch-icon" sizes="152x152" href="/assets/report/img/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/assets/report/img/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/assets/report/img/favicon/favicon-16x16.png">
	<link rel="manifest" href="/assets/report/img/favicon/manifest.json">
	<meta name="theme-color" content="#ffffff">
	<!-- Chrome, Firefox OS and Opera -->
	<meta name="theme-color" content="#000">
	<!-- Windows Phone -->
	<meta name="msapplication-navbutton-color" content="#000">
	<!-- iOS Safari -->
	<meta name="apple-mobile-web-app-status-bar-style" content="#000">

	<style>body { opacity: 0; overflow-x: hidden; }</style>
	<link rel="stylesheet" href="http://api.praweb.ru/assets/report/css/main.min.css">
        <link rel="stylesheet" href="http://api.praweb.ru/assets/report/css/custom.css">
	<script src="http://api.praweb.ru/assets/report/js/scripts.min.js"></script>
</head>

<body>
<script type="text/javascript">
var waypoint;
function initById(selector, offset, func, dataArr, dataObj) {
		 waypoint = new Waypoint({
		 element: document.getElementById(selector),
		 handler: function() {
			if(dataArr)
			    setTimeout(func(dataArr, selector), 1000);
		     if(dataObj)
			    setTimeout(func(dataObj, selector), 1000);
			this.destroy();
		 },
		 offset: offset
	  });
   }

   function myChartLine(dataObj, selector) {
	  console.log('selector = ', selector);
     var ctx = document.getElementById(selector).getContext('2d');
     var chart = new Chart(ctx, {
	    // The type of chart we want to create
	    type: 'line',
	    // The data for our dataset
	    data: dataObj,
	    options: {
		   maintainAspectRatio: false
	    }
     });
   }

</script>
	<nav id="my-menu">
		<ul>
			<li><a href="#position-1">Информация о проекте</a></li>
			<?php foreach($parts as $key => $p) { ?>
			<li><a href="#position-<?= Html::encode($p['order']+2); ?>"><?= Html::encode($p['name']); ?></a></li>
			<?php } ?>
		</ul>
	</nav>
<div id="my-content">
     <section class="static-elements">
          <a href="javascript:" id="return-to-top">
               <i class="fa fa-chevron-up" aria-hidden="true"></i>
          </a>
          <a href="#my-menu" class="hamburger hamburger--collapse" type="button" id="menu-btn">
            <span class="hamburger-box">
                <span class="hamburger-inner"></span>
            </span>
          </a>

          <div id="loading">
               <div id="loading-center">
                    <div id="loading-center-absolute">
                         <div class="object" id="object_four"></div>
                         <div class="object" id="object_three"></div>
                         <div class="object" id="object_two"></div>
                         <div class="object" id="object_one"></div>

                    </div>
               </div>

          </div>

     </section>

     <section class="content info-project" id="position-<?= Html::encode($order+1); ?>">
          <div class="container">
               <div class="row">
                    <div class="col-xs-12">
                         <h2 class="h2 menu-item" cont="<?= Html::encode(++$order); ?>">Информации о проекте</h2>
                    </div>
               </div>
               <div class="row">
                    <div class="col-xs-12 col-md-6 ">
                         <div class="about-proj">
                              <h2 class="h3">Описание</h2>
                              <table class="info-proj">
                                   <tbody>
                                   <tr>
                                        <td><span class="name-bg"> Проект: </span></td>
                                        <!--td><span>«Стоматология Алтей»</span> <br></td-->
                                        <td><span><?= Html::encode($project['name']); ?></span> <br></td>
                                   </tr>
                                   <tr>
                                        <td><span class="name-bg"> Адрес сайта: </span></td>
                                        <!--td><span><a target="_blank" href="https://alteident.ru">https://alteident.ru</a></span></td-->
                                        <td><span><a target="_blank" href="http://<?= Html::encode($project['url']); ?>"><?= Html::encode($project['url']); ?></a></span></td>
                                   </tr>
                                   <tr>
                                        <td><span class="name-bg"> Отчетный период: </span></td>
                                        <!--td><span>Июль</span></td-->
                                        <td><span><?= Html::encode($date); ?></span></td>
                                   </tr>
                                   <tr>
                                        <td> </td>
                                        <td> </td>
                                   </tr>
                                   </tbody>
                              </table>
                              <button class="btn-accent" onclick="window.print();"> Распечатать </button>
                              <button> Отправить на почту </button>
                         </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-3 contact-user">
                         <div class="accounter-ava" style="background: url('/assets/report/img/avatar/<? if($user['photo'] !== null) echo $user['photo']; else echo '1-1.jpg'; ?>')">

                         </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-3 contact-user">
                         <!--h2 class="h3">Сергей Солохин</h2-->
                         <h2 class="h3"><?= Html::encode($user['name']); ?> <?= Html::encode($user['surname']); ?></h2>
                         <!--h5 class="h5">Chief Financial Officer</h5-->
                         <h5 class="h5"><?php if($user['role']['name'] !== 'Менеджер проектов ') { ?>Менеджер проектов, <? } ?><?= Html::encode($user['position']); ?></h5>
                         <div class="contacts-main">
                              <h5 class="р5">Контактные данные</h5>
                              <!--a target="_blank" href="mailto: solokhin@praweb.ru">
                                   <i class="fa fa-envelope" aria-hidden="true"></i> solokhin@praweb.ru
                              </a-->
                              <a target="_blank" href="mailto:<?= Html::encode($user['email']); ?>">
                                   <i class="fa fa-envelope" aria-hidden="true"></i> <?= Html::encode($user['email']); ?>
                              </a>
						<a target="_blank" href="mailto:documents@praweb.ru">
                                   <i class="fa fa-envelope" aria-hidden="true"></i> documents@praweb.ru
                              </a>

                              <?php if (!empty($user['phone'])){ ?>
                              <a href="tel:<?= Html::encode($user['phone']); ?>">
                                   <i class="fa fa-phone-square" style="font-size: 18px;" aria-hidden="true"></i> <?= Html::encode($user['phone']); ?>
                              </a>
                              <?php } ?>
                              <a href="tel:+73422475552">
                                   <i class="fa fa-phone-square" style="font-size: 18px;" aria-hidden="true"></i> +7 (342) 247-55-52
                              </a>
                         </div>

                    </div>
               </div>
          </div>
     </section>
