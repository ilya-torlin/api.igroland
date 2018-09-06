<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;

class CategoryattachController extends ActiveController {

    public $modelClass = 'app\models\CategoryAttach';

    public function checkAccess($action, $model = null, $params = []) {
 
    }
    public function actions() {
        $actions = parent::actions();
        unset($actions['delete'], $actions['create'], $actions['update'],$actions['index'],$actions['view']);
        return $actions;
    }

    
      /**
     * @OA\Post(
     *     path="/categoryattach",
     *     summary="Создает новую связку категорий",
     *     tags={"categoryAttach"},
     *     security={{"bearerAuth":{}}},
     *     description="Метод для создания связки категорий  category_id - Категория в которую происходит привязывание (из собственного каталога),attached_category_id - Категория которая привязывается ",
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="category_id",
     *                     description="Категория в которую происходит привязывание (из собственного каталога)",
     *                     type="integer",
     *                 ),  
     *                @OA\Property(
     *                     property="attached_category_id",
     *                     description="Категория которая привязывается",
     *                     type="integer",
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
        
        $category = \app\models\Category::find()->where(['id' => $params['category_id']])->one();
        if (!$category){
             return JsonOutputHelper::getError('Категория category_id не найдена');
        }
        
        $catalog = \app\models\Catalog::find()->where(['id' => $category->catalog_id])->one();
        if (!$catalog || ($catalog->user_id != $me->id && $me->role_id != 1) ){
             return JsonOutputHelper::getError('Категория  не принадлежит пользователю');
        }
        $catalog->setAndSaveUpdate();
        $acategory = \app\models\Category::find()->where(['id' => $params['attached_category_id']])->one();
        if (!$acategory){
             return JsonOutputHelper::getError('Категория attached_category_id не найдена');
        }
        
        //
        $aucatalog = \app\models\UserCatalog::find()->where(['catalog_id' => $acategory->catalog_id,'user_id' => $me->id])->one();
        $acatalog = \app\models\Catalog::find()->where(['id' => $acategory->catalog_id])->one();
        if (!$acatalog->avlForAll && ($acatalog->user_id != $me->id) && !$aucatalog && ($me->role_id != 1) ){
             return JsonOutputHelper::getError('Привязываемая категория не доступна пользователю');
        }
        
        $categoryAttach = \app\models\CategoryAttach::find()->where(['category_id' => $params['category_id'],'attached_category_id' => $params['attached_category_id']])->one();
        
        if (!$categoryAttach){
             $categoryAttach = new \app\models\CategoryAttach();
             $categoryAttach->category_id = $params['category_id'];
             $categoryAttach->attached_category_id = $params['attached_category_id'];
             $categoryAttach->save();
        }
       
       
        
        return JsonOutputHelper::getResult($categoryAttach);
    }
   

       /**
     * @OA\Delete(
     *     path="/categoryattach/{id}",
     *     summary="Удаляет связку категорий",
     *     tags={"categoryAttach"},
     *     description="Метод для удаления связки категорий",
     *     security={{"bearerAuth":{}}}, 
     *    @OA\Parameter(
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
        $categoryAttach = \app\models\CategoryAttach::find()->where(['id' => $id])->one();
        
        $category = \app\models\Category::find()->where(['id' => $categoryAttach->category_id])->one();
        if (!$category){
             return JsonOutputHelper::getError('Категория category_id из связки не найдена');
        }
        
        $catalog = \app\models\Catalog::find()->where(['id' => $category->catalog_id,'user_id' => $me->id])->one();
        if (!$catalog){
             return JsonOutputHelper::getError('Категория  не принадлежит пользователю');
        }
        $catalog->setAndSaveUpdate();
        $categoryAttach->delete();
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
