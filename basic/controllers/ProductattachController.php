<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;

class ProductattachController extends ActiveController {

    public $modelClass = 'app\models\ProductAttach';

    public function checkAccess($action, $model = null, $params = []) {
        
    }

    public function actions() {
        $actions = parent::actions();
        unset($actions['delete'], $actions['create'], $actions['update'], $actions['index'], $actions['view']);
        return $actions;
    }

    /**
     * @OA\Post(
     *     path="/productattach",
     *     summary="Создает новую связку товара и категории",
     *     tags={"productAttach"},
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
     *                     property="attached_product_id",
     *                     description="Товар который привязывается",
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
        if (!$category) {
            return JsonOutputHelper::getError('Категория category_id не найдена');
        }        
        $catalog = \app\models\Catalog::find()->where(['id' => $category->catalog_id])->one();
        if (!$catalog || ($catalog->user_id != $me->id && $me->role_id != 1) ){
             return JsonOutputHelper::getError('Категория  не принадлежит пользователю');
        }
        $catalog->setAndSaveUpdate();
        $aproduct = \app\models\Product::find()->where(['id' => $params['attached_product_id']])->one();
        if (!$aproduct) {
            return JsonOutputHelper::getError('Товар attached_product_id не найден');
        }


        $productAttach = \app\models\ProductAttach::find()->where(['category_id' => $params['category_id'], 'attached_product_id' => $params['attached_product_id']])->one();

        if (!$productAttach) {
            $productAttach = new \app\models\ProductAttach();
            $productAttach->category_id = $params['category_id'];
            $productAttach->attached_product_id = $params['attached_product_id'];
            $productAttach->save();
        }



        return JsonOutputHelper::getResult($productAttach);
    }

    /**
     * @OA\Delete(
     *     path="/productattach/{id}",
     *     summary="Удаляет связку категории и товара",
     *     tags={"productAttach"},
     *     description="Метод для удаления связки категории и товара",
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
        $productAttach = \app\models\ProductAttach::find()->where(['id' => $id])->one();
        if (!$productAttach) {
            return JsonOutputHelper::getError('Связка с таким id не найдена');
        }
        $category = \app\models\Category::find()->where(['id' => $productAttach->category_id])->one();
        if (!$category) {
            return JsonOutputHelper::getError('Категория category_id из связки не найдена');
        }

        $catalog = \app\models\Catalog::find()->where(['id' => $category->catalog_id, 'user_id' => $me->id])->one();
        if (!$catalog) {
            return JsonOutputHelper::getError('Категория  не принадлежит пользователю');
        }
        $catalog->setAndSaveUpdate();
        $productAttach->delete();
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
