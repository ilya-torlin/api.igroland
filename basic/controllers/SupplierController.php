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
               'code' =>  $model['code'],
               'link' => $model['link'],
               'price_add' => $model['price_add'],
               'sort' => $model['sort'],
               'importIsActive' => $model['importIsActive']
            );
            $data[] = (object)$item;                      
        }
        return $data;        
    }
    
    public function checkAccess($action, $model = null, $params = []) {
        
    }
     /**
     * @OA\Get(
     *     path="/supplier",
     *     summary="Возвращает список поставщиков",
     *     tags={"supplier"},
     *     description="Метод для для получения списка поставщиков",
     *     security={{"bearerAuth":{}}},      
     *    
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *     @OA\Response(
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
        $models = \app\models\Supplier::find()->orderBy('sort ASC')->asArray()->all();
        $data = $this->prepareData($models);
        return JsonOutputHelper::getResult($data);
    }

     /**
      * @OA\Put(
      *     path="/supplier/{id}",
      *     summary="Обновляет данные поставщика",
      *     tags={"supplier"},
      *     description="Метод для обновления данных поставщиков",
      *     security={{"bearerAuth":{}}},
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         required=false,
      *         @OA\Schema(
      *             type="integer",
      *         )
      *     ),
      *      @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                  @OA\Property(
      *                     property="link",
      *                     description="link",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="price_add",
      *                     description="price_add",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="sort",
      *                     description="sort",
      *                     type="string",
      *                 ),
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="successful operation"
      *     ),
      *     @OA\Response(
      *         response=401,
      *         description="Необходимо отправить авторизационный токен"
      *     )
      *
      * )
      */
     public function actionUpdate($id)
     {
          $params = \Yii::$app->request->post();
          $me = \Yii::$app->user->identity;
          if ($me->role_id != 1) {
               return JsonOutputHelper::getError('Доступно только для пользовтеля с ролью администратора');
          }
          $model = \app\models\Supplier::find()->where(['id' => $id])->limit(15)->one();
          $model->setAttributes($params);
          $model->validate();
          $model->save();
     }
     /**
      * @OA\Post(
      *     path="/supplier/{id}/setonoff",
      *     summary="Блокирует каталог",
      *     tags={"supplier"},
      *     description="Метод для блокировки каталога",
      *     security={{"bearerAuth":{}}},
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         required=false,
      *         @OA\Schema(
      *             type="integer",
      *         )
      *     ),
      *     @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                 @OA\Property(
      *                     property="value",
      *                     description="value",
      *                     type="string",
      *                 )
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="successful operation"
      *     ),
      *     @OA\Response(
      *         response=401,
      *         description="Необходимо отправить авторизационный токен"
      *     ),

      * )
      */
     public function actionSetonoff($id){
          $me = \Yii::$app->user->identity;
          if ($me->role_id != 1){
               return JsonOutputHelper::getError('Только пользователям с ролью Супер пользователя доступно получение списка пользователей');
          }
          $model = \app\models\Supplier::find()->where(['id' => $id])->one();
          if(!$model)
               return JsonOutputHelper::getError('Пользователь не найден');
          $boolval = filter_var(\Yii::$app->request->post()['value'], FILTER_VALIDATE_BOOLEAN);
          if ($boolval) {
               $model->importIsActive = 1;
          } else {
               $model->importIsActive = 0;
          }
          $model->save();
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
