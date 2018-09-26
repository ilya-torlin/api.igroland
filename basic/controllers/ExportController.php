<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;

class ExportController extends ActiveController {

    public $modelClass = 'app\models\Export';
    public $result = array('shop' => array('name' => 'Analyze-IT', 'url' => 'http://analyze-it.su'), 'categories' => array(), 'offers' => array());
    public $tmarray = array();
    public $emptyImagesArray = array();
    public $productIds = array();
    public $export;
    
    public function addProcent($value,$procent){
        $res = (float)($value * ((100+$procent) / 100));
        return number_format($res, 2,'.','');
    }

    public function checkAccess($action, $model = null, $params = []) {
        
    }

    public function prepareIndexData($list){
         $arrayList = [];
         foreach($list as $item){
              $arrayList[] = array(
                   'id' => $item['id'],
                   'name' => $item['name'],
                   'link' => $item['link'],
                   'blocked' =>  $item['blocked'],
              );
         }

         return $arrayList;
    }

    public function prepareViewData($item){
         return $array = array(
              'id' => $item['id'],
              'name' => $item['name'],
              'link' => $item['link'],
              'blocked' =>  $item['blocked'],
              'optPriceAdd' => $item['optPriceAdd'],
              'roznPriceAdd' => $item['roznPriceAdd'],
              'catalog' => array('id' => $item['catalog']['id'], 'name' => $item['catalog']['name'])
         );
    }

    public function actions() {
        $actions = parent::actions();
        unset($actions['delete'], $actions['create'], $actions['update'], $actions['index'], $actions['view']);
        return $actions;
    }

    private function getInnerCategories($category) {
        $this->result['categories'][] = array('name' => $category->title, 'id' => $category->id, 'parent_id' => $category->parent_id);
        $products = $category->getChildProducts()->with(['brand', 'productImages', 'productImages.image'])->all();
        foreach ($products as $product) {
            if (!in_array($product->id, $this->productIds)) {
                $this->productIds[] = $product->id;
                $image = '';

                foreach ( $product->productImages as $productImage) {
                    if (($product->useAdminGallery && ($productImage->type == 'ADMIN' )) ||
                            (!$product->useAdminGallery && ($productImage->type == 'SUPPLIER' ))){
                        $image = \Yii::$app->params['imageUrls']["ADMIN"] . $productImage->image->path;
                        break;
                    }                        
                }

                if(empty($image)){
                     $this->emptyImagesArray[] = $product->sku;
                     continue;
                }
                
                $price = $product->price;
                if (array_key_exists($product->id, $this->tmarray)){
                    $price = $this->addProcent($price, $this->tmarray[$product->id]);
                } else {
                    $price = $this->addProcent($price, $this->export->optPriceAdd); 
                }
                
                $roznPrice = $this->addProcent($price, $this->export->roznPriceAdd); 
                
                
                $this->result['offers'][] = array('name' => $product->title,
                    'id' => $product->id,
                    'categoryId' => $category->id,
                    'articul' => $product->sku,
                    'picture' => $image,
                    'price' => $price,
                    'roznPrice' => $roznPrice,
                    'supplier_price' => $product->supplier_price,
                    'brand' => $product->brand? $product->brand->title: '',
                    'supplier' => $product->supplier_id,
                    'barcode' => $product->barcode,
                    'width' => $product->width,
                    'height' => $product->height,
                    'depth' => $product->depth,
                    'unit' => $product->unit,
                    'weight' => $product->weight,
                    'box' => $product->pack,
                    'min' => $product->min_order?$product->min_order:1,
                    'quantity' => $product->quantity,
                    'hit' => $product->hit,
                    'pack' => $product->pack,
                    'sku' => $product->sku,
                    'sale' => $product->sale
                    );
            }
        }


        $categories = \app\models\Category::find()->where(['parent_id' => $category->id])->all();
        foreach ($categories as $category) {
            $this->getInnerCategories($category);
        }
    }

    /**
     * @OA\Get(
     *     path="/export/{link}",
     *     summary="Возвращает xml каталог",
     *     tags={"export"},
     *     description="Метод для получения xml каталога",
     *    @OA\Parameter(
     *         name="link",
     *         in="path",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
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
    public function actionView($link) {

         ini_set('memory_limit', '3072M');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_XML;



        $this->export = \app\models\Export::find()->where(['link' => $link])->one();
        if (!$this->export) {
            return JsonOutputHelper::getError('Экспорт не найден');
        }

        $tm = \app\models\TradeMarkup::find()->where(['catalog_id' =>  $this->export->catalog_id])->select(['product_id','value'])->asArray()->all();
        foreach ($tm as $item){
           $this->tmarray[$item['product_id']] =  $item['value'];
        }

        $categories = \app\models\Category::find()->where(['catalog_id' =>  $this->export->catalog_id, 'parent_id' => null])->all();
        foreach ($categories as $category) {
            $this->getInnerCategories($category);
        }

         $filepath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'assets/empty_images_' . time() . '.txt';
         $this->emptyImagesArray[] = count($this->emptyImagesArray);
         file_put_contents($filepath, json_encode($this->emptyImagesArray));

        return $this->result;
        $products = array();
        $categories = array();

        $category = \app\models\Category::find()->where(['id' => $id])->with(['attachedCategories'])->one();
        if (!$category) {
            return JsonOutputHelper::getError('Категория не найдена');
        }

        $catalog = \app\models\Catalog::find()->where(['id' => $category->catalog_id, 'user_id' => $me->id])->one();
        if (!$catalog) {
            return JsonOutputHelper::getError('Категория  не принадлежит пользователю');
        }

        $models = array($category);
        $data = $this->prepareData($models, 0, 0, false, true);
        return JsonOutputHelper::getResult($data['catalogFolders'][0]);
    }
     /**
      * @OA\Post(
      *     path="/export/view",
      *     summary="Возвращает информацию приложения",
      *     tags={"export"},
      *     description="Метод для получения информации приложения",
      *      @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                 @OA\Property(
      *                     property="id",
      *                     description="id",
      *                     type="integer",
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
     public function actionViewexport(){
          $params = \Yii::$app->request->post();
          if (!isset($params['id'])) {
               return JsonOutputHelper::getError('Не задан идентификатор приложения');
          }
          $exportApp = \app\models\Export::find()->with('catalog')->where(['id' => $params['id']])->asArray()->one();
          $model = $this->prepareViewData($exportApp);
          return JsonOutputHelper::getResult($model);
     }
     /**
      * @OA\Get(
      *     path="/export",
      *     summary="Возвращает список приложений",
      *     tags={"export"},
      *     description="Метод для получения списка каталога",
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
    public function actionIndex(){
          $user = \Yii::$app->user->identity;
          $exportApps = \app\models\Export::find()->where(['user_id' => $user->id])->limit(100)->all();
          $model = $this->prepareIndexData($exportApps);
          return JsonOutputHelper::getResult($model);
    }
     /**
      * @OA\Post(
      *     path="/export/search",
      *     summary="Возвращает данные поиска",
      *     tags={"export"},
      *     description="Метод для для получения искомых данных приложений",
      *     security={{"bearerAuth":{}}},
      *     @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                 @OA\Property(
      *                     property="text",
      *                     description="text",
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
     public function actionSearch() {
          $me = \Yii::$app->user->identity;
          $params = \Yii::$app->request->post();
          if (!isset($params['text'])) {
               return JsonOutputHelper::getError('Не заполнен поисковый текст');
          }
          $apps = \app\models\Export::find()->where(['like', 'name', $params['text']]);
          $apps = $apps->limit(1000)->all();
          $data = $this->prepareIndexData($apps);
          return JsonOutputHelper::getResult($data);
     }

     /**
      * @OA\Post(
      *     path="/export/{id}/setonoff",
      *     summary="Блокирует приложение",
      *     tags={"export"},
      *     description="Метод для блокировки приложение",
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

          $model = \app\models\Export::find()->where(['id' => $id])->one();
          if(!$model)
               return JsonOutputHelper::getError('Приложение не найдено');
          $boolval = filter_var(\Yii::$app->request->post()['value'], FILTER_VALIDATE_BOOLEAN);
          if ($boolval) {
               $model->blocked = 1;
          } else {
               $model->blocked = 0;
          }
          $model->save();
     }

     public function generateStringLine(){
          return uniqid() . uniqid();
     }
     /**
      * @OA\Post(
      *     path="/export/generate",
      *     summary="Генерирует новый ключ приложения",
      *     tags={"export"},
      *     description="Метод для генерации нового ключа приложения",
      *     security={{"bearerAuth":{}}},
      *     @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                 @OA\Property(
      *                     property="id",
      *                     description="id",
      *                     type="integer",
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
     public function actionGeneratenewlink(){
          $user = \Yii::$app->user->identity;
          $params = \Yii::$app->request->post();
          if (!isset($params['id']))
               return JsonOutputHelper::getError('Не указан id приложения');

          $model = \app\models\Export::find()->where(['id' => $params['id']])->one();

          if ($user->id != $model->user_id )
               throw new \yii\web\ForbiddenHttpException(sprintf('Недостатоно прав для редактирования'));

          $model->link = $this->generateStringLine();
          $model->save();

          return JsonOutputHelper::getResult(array( 'link' => $model->link));

     }

     /**
      * @OA\Post(
      *     path="/export",
      *     summary="Создаем приложение",
      *     tags={"export"},
      *     description="Метод для создания приложение",
      *     security={{"bearerAuth":{}}},
      *     @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                 @OA\Property(
      *                     property="type",
      *                     description="type",
      *                     type="integer",
      *                 ),
      *                 @OA\Property(
      *                     property="catalog",
      *                     description="catalog",
      *                     type="integer",
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
      *     ),
      * )
      */
     public function actionAdd(){
          $me = \Yii::$app->user->identity;
          $params = \Yii::$app->request->post();
          if (!isset($params['type']))
               return JsonOutputHelper::getError('Тип приложения не указан');
          if (!isset($params['catalog']))
               return JsonOutputHelper::getError('Каталог не указан');

          $model = new \app\models\Export();

          $typeValue = intval($params['type']);

          if ($typeValue === 2) {
               $model->link = $this->generateStringLine();
               $model->user_id = $me->id;
               $model->catalog_id = intval($params['catalog']);
               $model->save();
               $model->name = 'Приложение#'.$model->id;
               $model->save();
          } else {
               return JsonOutputHelper::getError('Пока можно создать только тип файл');
          }

          return JsonOutputHelper::getResult(array( 'added_id' => $model->id));
     }
     /**
      * @OA\Delete(
      *     path="/export/{id}",
      *     summary="Удаляет приложение по id",
      *     tags={"export"},
      *     description="Метод для удаления приложения",
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
          $user = \Yii::$app->user->identity;
          $model = \app\models\Export::find()->where(['id' => $id])->one();

          if ($user->id != $model->user_id )
               throw new \yii\web\ForbiddenHttpException(sprintf('Недостатоно прав для удаления'));

          $model->delete();
     }
     /**
      * @OA\Put(
      *     path="/export/{id}",
      *     summary="Обновляет данные приложения",
      *     tags={"export"},
      *     description="Метод для обновления приложения",
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
      *                     property="name",
      *                     description="name",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="optPriceAdd",
      *                     description="optPriceAdd",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="roznPriceAdd",
      *                     description="roznPriceAdd",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="catalog_id",
      *                     description="catalog_id",
      *                     type="integer",
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
      * )
      */
     public function actionUpdate($id) {
          $user = \Yii::$app->user->identity;
          $params = \Yii::$app->request->post();

          if (!isset($params['name']))
               return JsonOutputHelper::getError('Имя не передано');
          if (!isset($params['catalog_id']))
               return JsonOutputHelper::getError('Каталог не указан');

          $model = \app\models\Export::find()->where(['id' => $id])->one();

          if ($user->id != $model->user_id )
               throw new \yii\web\ForbiddenHttpException(sprintf('Недостатоно прав для редактирования'));

          $model->setAttributes($params);
          $model->validate();
          \Yii::trace(json_encode($model->getErrors()), __METHOD__);
          $model->save();
          return JsonOutputHelper::getResult('');
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
        $behaviors['authenticator']['except'] = ['options', 'view'];

        return $behaviors;
    }

}
