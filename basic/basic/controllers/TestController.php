<?php

namespace app\controllers;

use yii;
use yii\web\Controller;

class TestController extends Controller {

    public function actionIndex() {
//    	echo '<a href="https://oauth.yandex.ru/authorize?&response_type=token&client_id=1a0b9a682a6844c58d7e29ee5d01cb22&state=2">получить токен</a>';
//    	if (isset($_GET['state'])){
//    		echo 'Токен : ' . $_GET['access_token'] .' получен для проекта с id='.$_GET['state'];
//    	}

    	//\Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;

    	//$result = Yii::$app->metrikaHelper::getCommonTable('40293289','AQAEA7qhh45iAASGyvMm5ojaGEAQm3Ta0-eqdnE',false,false); 

    	//Источник: поисковые системы
    	//$result = Yii::$app->metrikaHelper::getSearchSystem('40293289','AQAEA7qhh45iAASGyvMm5ojaGEAQm3Ta0-eqdnE',false,false);

    	//Источник: переходы по ссылкам
    	//$result = Yii::$app->metrikaHelper::getFollowLink('40293289','AQAEA7qhh45iAASGyvMm5ojaGEAQm3Ta0-eqdnE',false,false);

    	//Источник: прямые заходы
    	//$result = Yii::$app->metrikaHelper::getStraight('40293289','AQAEA7qhh45iAASGyvMm5ojaGEAQm3Ta0-eqdnE',false,false);

    	//Источник: реклама (если есть)
    	//$result = Yii::$app->metrikaHelper::getAdvertising('40293289','AQAEA7qhh45iAASGyvMm5ojaGEAQm3Ta0-eqdnE',false,false);
         //$pe =   \app\components\report\ReportPartEngineFactory::create('PositionTopLineReportPartEngine');
       $project = \app\models\Project::find()->where(['id' => 38])->one();
        $dates = Yii::$app->allPositionsHelper::getReportDates($project->all_positions_id);
       \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;       
        var_dump($dates);
        die();
    }

}
