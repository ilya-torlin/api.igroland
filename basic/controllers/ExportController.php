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
    public $productIds = array();
    public $export;
    
    public function addProcent($value,$procent){
        $res = (float)($value * ((100+$procent) / 100));
        return number_format($res, 2,'.','');
    }

    public function checkAccess($action, $model = null, $params = []) {
        
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

    public function actionIndex(){
         //$me = \Yii::$app->user->identity;
         //if ($me->role_id != 1){
         //     return JsonOutputHelper::getError('Только пользователям с ролью Супер пользователя доступно получение списка пользователей');
         //}

         $exportApps = \app\models\Export::find()->limit(100)->all();

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
