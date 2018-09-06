<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;
/**
 * MVC controller that handles "category/*" urls.
 */
class SupplierController extends ActiveController {

    public $modelClass = 'app\models\Supplier';
    
    
    private function prepareData($models){
        $data = array();       
        foreach ($models as $model){
            $item = array(
               'id' => $model['id'],
               'title' =>  $model['title'], 
               'code' =>  $model['code']
            );
            $data[] = (object)$item;                      
        }
        return $data;        
    }
    
    public function checkAccess($action, $model = null, $params = []) {
        
    }
 /**
     * @OAS\Get(
     *     path="/supplier",
     *     summary="Возвращает список поставщиков",
     *     tags={"supplier"},
     *     description="Метод для для получения списка поставщиков",
     *     security={{"bearerAuth":{}}},      
     *    
     *     @OAS\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *     @OAS\Response(
     *         response=401,
     *         description="Необходимо отправить авторизационный токен"
     *     ),

     * )
     */
    public function actionIndex() {
        $me = \Yii::$app->user->identity;
           if ($me->role_id != 1){
                 return JsonOutputHelper::getError('Доступно только для пользовтеля с ролью администратора');
           }
        $models = \app\models\Supplier::find()->orderBy('title ASC')->asArray()->all();    
        $data =    $this->prepareData($models);
        return JsonOutputHelper::getResult($data);
    }
    

    
    
    public function actions() {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['view'], $actions['delete']);
        return $actions;
    }

    
  

  

   

    

   

    public function behaviors() {

        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];



        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => ['Origin' => ['*']]];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

}
