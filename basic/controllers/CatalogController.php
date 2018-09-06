<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;

/**
 * MVC controller that handles "category/*" urls.
 */
class CatalogController extends ActiveController {

    public $modelClass = 'app\models\Catalog';

    private function prepareDataIndex($models) {
        $data = array();
        $idx = 0;
        foreach ($models as $model) {

            $item = array(
                'catalogName' => $model->name,
                'id' => $model->id,
            );
            array_push($data, $item);
            $idx++;
        }
        return $data;
    }

    private function prepareData($models) {
        $data = array();
        $idx = 0;
        foreach ($models as $model) {
            $isActive = false;
            $img = '';
            if ($model->image) {
                $img = $model->image->path;
            }
            $users = array();
            foreach ($model->users as $user) {
                $users[] = (object) array('name' => $user->email, 'id' => $user->id);
            }

            $item = array(
                'id' => $model->id,
                'selected' => null,
                'switcherActive' => $model->avlForAll,
                'showConfig' => false,
                'catalogName' => $model->name,
                'isActive' => $isActive,
                'isOn' => $model->isOn,
                'description' => $model->description,
                'lastUpdate' => date('d.m.Y H:i',strtotime($model->timestamp)),
                'catalogImg' => $img,
                'catalogSaved' => true,
                'selectedUsers' => $users,
            );


            array_push($data, $item);
            $idx++;
        }
        return $data;
    }

    public function checkAccess($action, $model = null, $params = []) {
        
    }

    public function actions() {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['view'], $actions['delete']);
        return $actions;
    }

    /**
     * @OA\Get(
     *     path="/catalog",
     *     summary="Возвращает список каталогов доступных  для просмотра пользвоателю",
     *     tags={"catalog"},
     *     description="Метод для для получения данных пользоватлея",
     *     security={{"bearerAuth":{}}}, 
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
        if ($me->role_id == 1) {
             $models = \app\models\Catalog::find()->where(['IS NOT','supplier_id', null]);
        } else {
              $models = \app\models\Catalog::find()
                ->leftJoin('user_catalog', 'user_catalog.catalog_id = catalog.id')
                ->andWhere(['<>','catalog.user_id',$me->id])
                ->andWhere(['catalog.isOn' => 1])
                ->andWhere(['or',
                    ['user_catalog.user_id' => $me->id],
                    ['catalog.avlForAll'=>1]
                 ]);
        }
      
               
                
        $models = $models->orderBy('name ASC')->all();
        $data = $this->prepareDataIndex($models);
        return JsonOutputHelper::getResult($data);
    }

      /**
     * @OA\Get(
     *     path="/catalog/{id}",
     *     summary="Возвращает каталог",
     *     tags={"catalog"},
     *     description="Метод для получения каталога",
     *     security={{"bearerAuth":{}}}, 
       * @OA\Parameter(
     *         name="id",
     *         in="path",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
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
    public function actionView($id) {
        $me = \Yii::$app->user->identity;
        $model = \app\models\Catalog::find()->where(['id' => $id])->with(['users', 'image'])->one();
        $model->setAndSaveUpdate();
        if (!$model || ($me->role_id != 1 && ($model->user_id != $me->id) )){
             return JsonOutputHelper::getError('Каталог не найден или не принадлежит пользователю');
        }        
        $models = array($model);
        $data = $this->prepareData($models);
        return JsonOutputHelper::getResult($data[0]);
    }
    
    /**
     * @OA\Get(
     *     path="/catalog/my",
     *     summary="Возвращает список каталогов пользователя",
     *     tags={"catalog"},
     *     description="Метод для для получения данных пользоватлея",
     *     security={{"bearerAuth":{}}}, 
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
    public function actionGetmy() {
        $me = \Yii::$app->user->identity;
        if ($me->role_id == 1) {
             $models = \app\models\Catalog::find()->innerjoin(['user','user.id = catalog.user_id'])->where(['user.role_id' => 1])->andWhere(['IS NOT','catalog.user_id', null]);
        } else {
            $models = \app\models\Catalog::find()->where(['user_id' => $me->id]);
        }
        $models = $models->with(['users', 'image']);
        $models = $models->orderBy('name ASC')->all();
        $data = $this->prepareData($models);
        return JsonOutputHelper::getResult($data);
    }

    /**
     * @OA\Post(
     *     path="/catalog",
     *     summary="Создает новый каталог для текущего пользователя",
     *     tags={"catalog"},
     *     security={{"bearerAuth":{}}},
     *     description="Метод для создания пользовательского каталога",
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     description="Name",
     *                     type="string",
     *                 ),  
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Необходимо отправить авторизационный токен"
     *     ),


     * )
     */
    public function actionCreate() {
        $me = \Yii::$app->user->identity;
        $params = \Yii::$app->request->post();
        $catalog = new \app\models\Catalog();
        $catalog->isOn = 0;
        $catalog->name = $params['name'];
        $catalog->user_id = $me->id;
        $catalog->save();
        return JsonOutputHelper::getResult($catalog);
    }

    /**
     * @OA\Put(
     *     path="/catalog/{id}",
     *     summary="Обновляет каталог пользователя",
     *     tags={"catalog"},
     *     security={{"bearerAuth":{}}},
     *     description="Метод для обновления каталога пользователя",
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
     *                     property="catalogName",
     *                     description="catalogName",
     *                     type="string",
     *                 ),  
     *                 @OA\Property(
     *                     property="description",
     *                     description="description",
     *                     type="string",
     *                 ), 
     *                @OA\Property(
     *                     property="isActive",
     *                     description="isActive",
     *                     type="boolean",
     *                 ),
     *                @OA\Property(
     *                     property="selectedUsers",
     *                     description="selectedUsers",
     *                     type="array",
     *                    @OA\Items(
     *                      type="object",
     *                      @OA\Property(
     *                         property="name",
     *                         description="name",
     *                         type="string",
     *                       ),  
     *                       @OA\Property(
     *                         property="id",
     *                         description="id",
     *                         type="integer",
     *                       )
     *                      )
     * 
     *                      
     *                 ), 
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Необходимо отправить авторизационный токен"
     *     ),


     * )
     */
    public function actionUpdate($id) {
        $me = \Yii::$app->user->identity;
        $params = \Yii::$app->request->post();
        $catalog = \app\models\Catalog::find()->where(['id' => $id])->one();
        if (!$catalog || ($me->role_id != 1 && ($catalog->user_id != $me->id) )){
             return JsonOutputHelper::getError('Каталог не найден или не принадлежит пользователю');
        }
        
        $catalog->name = $params['catalogName'];
        $catalog->description = $params['description'];
        if ($params['switcherActive']){
            $catalog->avlForAll = 1;
        } else {
             $catalog->avlForAll = 0;
        }
        $catalog->save();
        
        $userIds = array();
        if (!is_array($params['selectedUsers'])){
            $users = json_decode('['.$params['selectedUsers'].']',true);
        } else {
            $users = $params['selectedUsers'];
        }
        
        
        foreach($users as $user){
           $userIds[] =  $user['id'];
        }
        
        
        
        
        
        \app\models\UserCatalog::deleteAll([
            'AND', 'catalog_id = :attribute_2', [
                'NOT IN', 'user_id',
                $userIds
            ]
                ], [
            ':attribute_2' => $id
        ]);




        foreach ($userIds as $uid) {
            $uc = \app\models\UserCatalog::find()->where(['user_id' => $uid, 'catalog_id' => $id])->one();
            if (!$uc) {
                $uc = new \app\models\UserCatalog();
                $uc->catalog_id = $id;
                $uc->user_id = $uid;
                $uc->save();
            }
        }
        
        
        
     
        
        
        $catalog = \app\models\Catalog::find()->where(['id' => $id])->one();
        $res = $this->prepareData(array($catalog));
        return JsonOutputHelper::getResult($res[0]);
    }

    /**
     * @OA\Post(
     *     path="/catalog/{id}/setonoff",
     *     summary="Включает \ выключает каталог",
     *     tags={"catalog"},
     *     description="",
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

     * )
     */
   
    public function actionSetonoff($id) {
        $me = \Yii::$app->user->identity;
        $catalog = \app\models\Catalog::find()->where(['user_id' => $me->id, 'id' => $id])->one();
        if (!$catalog) {
            return JsonOutputHelper::getError('Каталог не найден или не принадлежит пользователю');
        }
        $boolval = filter_var(\Yii::$app->request->post()['value'], FILTER_VALIDATE_BOOLEAN);
        if ($boolval) {
            $catalog->isOn = 1;
        } else {
            $catalog->isOn = 0;
        }

        $catalog->save();
        return JsonOutputHelper::getResult($catalog);
    }
    
     /**
     * @OA\Delete(
     *     path="/catalog/{id}",
     *     summary="Удаляет каталог",
     *     tags={"catalog"},
     *     description="Метод для удаления каталога",
     *     security={{"bearerAuth":{}}}, 
       * @OA\Parameter(
     *         name="id",
     *         in="path",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
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
    public function actionDelete($id) {
        $me = \Yii::$app->user->identity;        
        $catalog = \app\models\Catalog::find()->where(['id' => $id,'user_id' => $me->id])->one();
        if (!$catalog){
             return JsonOutputHelper::getError('Каталог  не принадлежит пользователю');
        }      
        $catalog->delete();
        return JsonOutputHelper::getResult(true);
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
