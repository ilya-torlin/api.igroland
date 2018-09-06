<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;
use app\components\ImageSaveHelper;

/**
 * MVC controller that handles "product/*" urls. 
 */
class ProductController extends ActiveController {

    public $modelClass = 'app\models\Product';

    private function prepareData($models, $params) {
        $data = array('items' => array(), 'data' => $params);
        $idx = 0;
        foreach ($models as $model) {
            $images = array();

            foreach ($model['productImages'] as $productImage) {
                if (($model['useAdminGallery'] && ($productImage['type'] == 'ADMIN' )) ||
                        (!$model['useAdminGallery'] && ($productImage['type'] == 'SUPPLIER' )))
                    $images[] = \Yii::$app->params['imageUrls']["ADMIN"] . $productImage['image']['path'];
            }



            $item = array(
                'id' => $model['id'],
                'name' => $model['title'],
                'price' => $model['price'],
                'idx' => $params['offset'] + $idx,
                'images' => $images,
            );

            array_push($data['items'], $item);
            $idx++;
        }
        return $data;
    }

    private function prepareDataDetail($model, $isAdmin = false) {
        $images = array();
        foreach ($model->productImages as $productImage) {
            if ($isAdmin) {
                $images[$productImage->type][] = \Yii::$app->params['imageUrls']["ADMIN"] . $productImage->image->path;
            } else {
                $images[] = \Yii::$app->params['imageUrls']["ADMIN"] . $productImage->image->path;
            }
        }


        $item = array(
            'id' => $model->id,
            'name' => $model->title,
            'import_title' => $model->import_title,
            'useAdminGallery' => $model->useAdminGallery,
            'tradeMarkup' => $model->tradeMarkup,
            'images' => $images,
            'params' => array(
                (object) array('key' => 'Артикул', 'val' => $model->sku),
                (object) array('key' => 'Бренд', 'val' => $model->brand? $model->brand->title:''),
                (object) array('key' => 'Код 1С', 'val' => $model->code1c),
                (object) array('key' => 'Штрихкод', 'val' => $model->barcode),
                (object) array('key' => 'id', 'val' => $model->id),
                (object) array('key' => 'Цена', 'val' => $model->price),
                (object) array('key' => 'Мин. партия', 'val' => $model->min_order),
                (object) array('key' => 'В коробке', 'val' => $model->pack),
                (object) array('key' => 'Количество', 'val' => $model->quantity),
                (object) array('key' => 'Страна', 'val' => $model->country)
            ),
        );


        return $item;
    }

    public function checkAccess($action, $model = null, $params = []) {
        
    }

    /**
     * @OA\Get(
     *     path="/product",
     *     summary="Возвращает список товаров c фильтрами",
     *     tags={"product"},
     *     description="",
     *     security={{"bearerAuth":{}}}, 
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *    @OA\Parameter(
     *         name="hideNotAvl",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="boolean",
     *         )
     *     ), 
     * 
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *    @OA\Parameter(
     *         name="offset",
     *         in="query",            
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
    public function actionIndex() {
        $params = \Yii::$app->request->get();



        if (!array_key_exists('category_id', $params) || empty($params['category_id'])) {
            return JsonOutputHelper::getError('Не задана category_id');
        }

        //Собираем массив всех вложенных\привязанных категорий
        $category = \app\models\Category::find()->where(['id' => $params['category_id']])->one();
        if (!$category) {
            return JsonOutputHelper::getError('Не найдена категория category_id');
        }
        $models = $category->getInnerProducts();

        if (array_key_exists('limit', $params) && !empty($params['limit'])) {
            $limit = intval($params['limit']);
        } else {
            $limit = 20;
        }
        
        if (array_key_exists('hideNotAvl', $params) && ($params['hideNotAvl'] == 'true' || $params['hideNotAvl'] == '1')) {           
                $models = $models->andWhere(['>', 'quantity', 0]);
        }
        
        
        $models = $models->limit($limit);



        if (array_key_exists('offset', $params) && !empty($params['offset'])) {
            $offset = intval($params['offset']);
        } else {
            $offset = 0;
        }

        $models = $models->offset($offset);

        $modelsCount = clone $models;
        $count = $modelsCount->count();
        $models = $models->orderBy('title ASC')->with(['brand', 'productImages', 'productImages.image'])->asArray()->all();
        $data = $this->prepareData($models, array('count' => $count, 'limit' => $limit, 'offset' => $offset));
        return JsonOutputHelper::getResult($data);
    }

    /**
     * @OA\Get(
     *     path="/product/{id}",
     *     summary="Возвращает  товар ",
     *     tags={"product"},
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

        $model = \app\models\Product::find()->where(['id' => $id])->with(['brand', 'productImages', 'productImages.image'])->one();
        if (!$model) {
            return JsonOutputHelper::getError('Не найден товар');
        }
        if ($me->role_id == 1) {
            $data = $this->prepareDataDetail($model, true);
        } else {
            $data = $this->prepareDataDetail($model);
        }

        return JsonOutputHelper::getResult($data);
    }

    /**
     * @OA\Get(
     *     path="/product/search",
     *     summary="Возвращает список  товаров по поиску",
     *     tags={"product"},
     *     description="Метод для для получения товаров по поиску",
     *     security={{"bearerAuth":{}}},   
     *    @OA\Parameter(
     *         name="text",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="category_id",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),    
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *    @OA\Parameter(
     *         name="offset",
     *         in="query",            
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
    public function actionSearch() {
        $params = \Yii::$app->request->get();
        if (!isset($params['text'])) {
            return JsonOutputHelper::getError('Не заполнен поисковый текст');
        }



        $models = \app\models\Product::find()->where(['like', 'title', $params['text']]);





        if (array_key_exists('category_id', $params) && !empty($params['category_id'])) {
            //Собираем массив всех вложенных\привязанных категорий
            $category = \app\models\Category::find()->where(['id' => $params['category_id']])->one();
            if (!$category) {
                return JsonOutputHelper::getError('Не найдена категория category_id');
            }
            $models = $category->getInnerProducts()->where(['like', 'title', $params['text']]);
        }

        if (array_key_exists('limit', $params) && !empty($params['limit'])) {
            $limit = intval($params['limit']);
            $models = $models->limit(intval($params['limit']));
        } else {
            $limit = 20;
        }
        $models = $models->limit($limit);



        if (array_key_exists('offset', $params) && !empty($params['offset'])) {
            $offset = intval($params['offset']);
        } else {
            $offset = 0;
        }

        $models = $models->offset($offset);

        $modelsCount = clone $models;
        $count = $modelsCount->count();
        $models = $models->orderBy('title ASC')->with(['brand', 'productImages', 'productImages.image'])->asArray()->all();
        $data = $this->prepareData($models, array('count' => $count, 'limit' => $limit, 'offset' => $offset));
        return JsonOutputHelper::getResult($data);
    }

    /**
     * @OA\Post(
     *     path="/product/{id}/settrademarkup",
     *     summary="Устанавливает индивидуальную наценку на продукт",
     *     tags={"product"},
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
     *                     type="integer",
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
    public function actionSettrademarkup($id) {
        $me = \Yii::$app->user->identity;
        $model = \app\models\Product::find()->where(['id' => $id])->one();
        if (!$model) {
            return JsonOutputHelper::getError('Не найден товар');
        }
        $trademarkup = \app\models\TradeMarkup::find()->where(['user_id' => $me->id, 'product_id' => $id])->one();
        if (!$trademarkup) {
            $trademarkup = new \app\models\TradeMarkup();
            $trademarkup->user_id = $me->id;
            $trademarkup->product_id = $id;
        }
        $trademarkup->value = \Yii::$app->request->post()['value'];
        $trademarkup->save();
        return JsonOutputHelper::getResult(array());
    }

    /**
     * @OA\Post(
     *     path="/product/{id}/setalternativetitle",
     *     summary="Устанавливает алтернативное имя для продукта (только для Администратора)",
     *     tags={"product"},
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
     *                     property="title",
     *                     description="title",
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
    public function actionSetalternativetitle($id) {
        $me = \Yii::$app->user->identity;
        if ($me->role_id != 1) {
            return JsonOutputHelper::getError('Доступно только для пользовтеля с ролью администратора');
        }
         $product = \app\models\Product::find()->where(['id' => $id])->one();
        if (!$product){
            return JsonOutputHelper::getError('Товар не найден');
        }
        $product->title = \Yii::$app->request->post()['title'];
        $product->save();
        return JsonOutputHelper::getResult(array());
    }

    /**
     * @OA\Post(
     *     path="/product/{id}/setuseadmingallery",
     *     summary="Устанавливает использовать ли галерею администратора",
     *     tags={"product"},
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
     *                     type="boolean",
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
    public function actionSetuseadmingallery($id) {
        $me = \Yii::$app->user->identity;
        if ($me->role_id != 1) {
            return JsonOutputHelper::getError('Доступно только для пользовтеля с ролью администратора');
        }
        $product = \app\models\Product::find()->where(['id' => $id])->one();
        if (!$product){
            return JsonOutputHelper::getError('Товар не найден');
        }
        $val = \Yii::$app->request->post()['value'];
        \Yii::trace(print_r($val, true));
        if ($val == 'true') {
            $val = 1;
        } else {
            $val = 0;
        }
        \Yii::trace(print_r($val, true));
        $product->useAdminGallery = $val;
        $product->save();
        return JsonOutputHelper::getResult($product);
    }

    /**
     * @OA\Post(
     *     path="/product/{id}/addgallery",
     *     summary="Загружает администраторскую галерею для товара",
     *     tags={"product"},
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
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                type="object",
     *                @OA\Property(
     *                     property="file",
     *                     description="file",
     *                     type="file",
     *                 ), 
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),

     * )
     */
    public function actionAddgallery($id) { 
        
        $me = \Yii::$app->user->identity;
        if ($me->role_id != 1) {
            return JsonOutputHelper::getError('Доступно только для пользовтеля с ролью администратора');
        }
        $product = \app\models\Product::find()->where(['id' => $id])->one();
        if (!$product){
            return JsonOutputHelper::getError('Товар не найден');
        }
        // заполняем поля из post
        $params = \Yii::$app->request->post();
        // получаем переданные файлы
        $files = \yii\web\UploadedFile::getInstancesByName('file');
        // заполняем директорию проекта

     
        foreach ($files as $file) {
           
            $data = ImageSaveHelper::saveFromFile($file);
            if (!$data) continue;

            $newImage = new \app\models\Image;
            $newImage->path = $data['link'];
            $newImage->save();

            $newProductImage = new \app\models\ProductImage;
            $newProductImage->product_id = $id;
            $newProductImage->image_id = $newImage->id;
            $newProductImage->type = "ADMIN";
            $newProductImage->save();
        }
        return JsonOutputHelper::getResult(true);
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
