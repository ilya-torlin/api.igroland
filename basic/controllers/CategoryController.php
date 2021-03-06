<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;

/**
 * MVC controller that handles "category/*" urls.
 */
class CategoryController extends ActiveController {

    public $modelClass = 'app\models\Category';

    private function prepareData($models, $lvlFolder = 0, $parent_folder_id = 0, $hideNotAvl = false, $isMyCatalog = false,$categoryIds = []) {
        $data = array('catalogFoldersKeyArr' => array(), 'catalogFolders' => array());
        $idx = 0;

        foreach ($models as $model) {
            $category = \app\models\Category::find()->where(['id' => $model['id']])->one();

            $goodsCount = $category->getInnerProducts();
            if ($hideNotAvl) {
                $goodsCount = $goodsCount->andWhere(['>', 'quantity', 0]);
            }
            $goodsCount = $goodsCount->count();






            $count = \app\models\Category::find()->where(['parent_id' => $model['id'],'deleted' => 0])->count();
           
            $item = array(
                'folderId' => $model['id'],
                'name' => $model['title'],
                'goodsCount' => $goodsCount,
                'lvlFolder' => $lvlFolder,
                'folderArr' => array(),
                'supplier_id' => $model['supplier_id'],
                'catalog_id' => $model['catalog_id'],
                'isOpen' => false,
                'hasFolders' => $count > 0,
                'hideFolder' => in_array((string)$model['id'], $categoryIds),
                'childCount' => 0,
                'wasOpened' => false,
                'wasDeleted' => false,
                // 'key' => $parent_folder_id.'_'.$idx, 
                'key' => $parent_folder_id . '_' . $idx . '_' . $model['id'],
                'isFixPrice' =>  (boolean) $model['isFixPrice'],
            );

            if ($isMyCatalog) {
                $attachedCategories = array();
                $attachedProducts = array();
                foreach ($model['categoryAttaches'] as $ca) {
                    $attachedCategories[] = array('id' => $ca['id'], 'attached_category_id' => $ca['attached_category_id'], 'attached_category_title' => $ca['attachedCategory']['title'].'('.$ca['attachedCategory']['catalog']['name'].')');
                }
                foreach ($model['productAttaches'] as $pa) {
                    $attachedProducts[] = array('id' => $pa['id'], 'attached_product_id' => $pa['attached_product_id'], 'attached_product_title' => $pa['attachedProduct']['title']);
                }
                $item['attachedCategories'] = $attachedCategories;
                $item['attachedProducts'] = $attachedProducts;
            }

            array_push($data['catalogFolders'], $item);
            $data['catalogFoldersKeyArr'][] = $parent_folder_id . '_' . $idx . '_' . $model['id'];

            $idx++;
        }
        return $data;
    }

    public function checkAccess($action, $model = null, $params = []) {
        
    }

    /**
     * @OA\Get(
     *     path="/category",
     *     summary="Возвращает список категорий",
     *     tags={"category"},
     *     description="Метод для для получения данных пользоватлея",
     *     security={{"bearerAuth":{}}},       
     *     @OA\Parameter(
     *         name="catalog_id",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *     @OA\Parameter(
     *         name="lvlFolder",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *     @OA\Parameter(
     *         name="id",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *    @OA\Parameter(
     *         name="parentId",
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
     *    @OA\Parameter(
     *         name="userCatalogId",
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
        $data = $this->prepareRequest();
        $data['models'] = $data['models']->orderBy('catalog_id ASC, title ASC')->asArray()->all();
         $params = \Yii::$app->request->get();
         $categoryIds = [];
        if (array_key_exists('userCatalogId', $params) && !empty($params['userCatalogId'])) {
            $models = \app\models\Category::find();
            $models = $models->innerJoin('category_attach', 'category_attach.attached_category_id = category.id');
            $models = $models->innerJoin('category as maincat', 'maincat.id = category_attach.category_id');
            $models = $models->where(['=', 'maincat.catalog_id', $params['userCatalogId']]);
            $categoryIds = $models->select('category.id')->asArray()->column();
        }
        
        $result = $this->prepareData($data['models'], $data['lvlFolder'], $data['parentFolderId'], $data['hideNotAvl'], $data['isMyCatalog'],$categoryIds);
        return JsonOutputHelper::getResult($result);
    }

    /**
     * @OA\Get(
     *     path="/category/search",
     *     summary="Возвращает список категорий подходящих по поиску",
     *     tags={"category"},
     *     description="Метод для для получения вложенных категорий",
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
     *         name="catalog_id",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *     @OA\Parameter(
     *         name="lvlFolder",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *     @OA\Parameter(
     *         name="id",
     *         in="query",            
     *         required=false,       
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ), 
     *    @OA\Parameter(
     *         name="parentId",
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
    private function prepareRequest() {
        $me = \Yii::$app->user->identity;
        $params = \Yii::$app->request->get();
        if (!array_key_exists('lvlFolder', $params) || !isset($params['lvlFolder'])) {
            $lvlFolder = 0;
        } else {
            $lvlFolder = $params['lvlFolder'] + 1;
        }
        if (!array_key_exists('id', $params) || empty($params['id'])) {
            $id = null;
        } else {
            $id = $params['id'];
        }

        if (array_key_exists('hideNotAvl', $params) && ($params['hideNotAvl'] == 'true' || $params['hideNotAvl'] == '1')) {
            $hideNotAvl = true;
        } else {
            $hideNotAvl = false;
        }
        if (!array_key_exists('parentId', $params) || empty($params['parentId'])) {
            $parentFolderId = 0;
        } else {
            $parentFolderId = $params['parentId'];
        }

        $models = \app\models\Category::find()
                ->innerJoin('catalog', 'catalog.id = category.catalog_id')
                ->leftJoin('user_catalog', 'catalog.id = user_catalog.catalog_id')
                ->leftJoin('user', 'user.id = catalog.user_id');

        if ($me->role_id == 1) {
            if (array_key_exists('catalog_id', $params) && !empty($params['catalog_id'])) {
                $models = $models->where(['or',
                    ['IS NOT', 'catalog.supplier_id', null],
                    ['user.role_id' => 1]
                ]);
            } else {
                $models = $models->where(['IS NOT', 'catalog.supplier_id', null]);
            }
        } else {
            $models = $models->where(['or',
                ['user_catalog.user_id' => $me->id],
                ['catalog.avlForAll' => 1],
                ['catalog.user_id' => $me->id]
            ]);
        }

        $models = $models->andWhere(['parent_id' => $id]);

        $isMyCatalog = 0;
        if (array_key_exists('catalog_id', $params) && !empty($params['catalog_id'])) {
            $models = $models->andWhere(['category.catalog_id' => $params['catalog_id']]);
            $catalog = \app\models\Catalog::find()->where(['id' => $params['catalog_id'], 'user_id' => $me->id])->one();
            if ($catalog || $me->role_id) {
                $isMyCatalog = 1;
                $models = $models->with(['categoryAttaches', 'categoryAttaches.attachedCategory', 'categoryAttaches.attachedCategory.catalog']);
                $models = $models->with(['productAttaches', 'productAttaches.attachedProduct']);
            }
        } else {
            $models = $models->andWhere(['catalog.isOn' => 1]);
        }

        $models = $models->andWhere(['deleted' => 0]);

        return array('models' => $models, 'lvlFolder' => $lvlFolder, 'parentFolderId' => $parentFolderId, 'hideNotAvl' => $hideNotAvl, 'isMyCatalog' => $isMyCatalog);
    }

    public function actionSearch() {
        $params = \Yii::$app->request->get();
        if (!isset($params['text'])) {
            return JsonOutputHelper::getError('Не заполнен поисковый текст');
        }

        $data = $this->prepareRequest();
        $data['models'] = $data['models']->andWhere(['like', 'title', $params['text']])->orderBy('catalog_id ASC, title ASC')->limit(1000)->asArray()->all();
        $result = $this->prepareData($data['models'], $data['lvlFolder'], $data['parentFolderId'], $data['hideNotAvl']);
        return JsonOutputHelper::getResult($result);
    }

    /**
     * @OA\Post(
     *     path="/category",
     *     summary="Создает новую категорию ",
     *     tags={"category"},
     *     security={{"bearerAuth":{}}},
     *     description="Метод для создания пользовательской категории",
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
     *                @OA\Property(
     *                     property="parentId",
     *                     description="Parent category id",
     *                     type="integer",
     *                 ),
     *                @OA\Property(
     *                     property="catalogId",
     *                     description="Catalog id",
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
        $category = new \app\models\Category();

        if (!array_key_exists('catalogId', $params) || !isset($params['catalogId'])) {
            return JsonOutputHelper::getError('Не указан catalogId');
        }
        $catalog = \app\models\Catalog::find()->where(['id' => $params['catalogId']])->one();
        $catalog->setAndSaveUpdate();
        if ($catalog->user_id != $me->id && $me->role_id != 1) {
            return JsonOutputHelper::getError('Каталог не принадлежит пользователю');
        }
        $category->catalog_id = $params['catalogId'];

        if (array_key_exists('parentId', $params) && !empty($params['parentId'])) {
            $parentCategory = \app\models\Category::find()->where(['id' => $params['parentId']])->one();
            if (!$parentCategory) {
                return JsonOutputHelper::getError('Не существует такой категории');
            }
            if ($parentCategory->catalog_id != $params['catalogId']) {
                return JsonOutputHelper::getError('catalogId родительской категории не совпадает с catalogId создаваемой');
            }
            $category->parent_id = $params['parentId'];
        }
        $category->title = $params['name'];
        $category->save();
        return JsonOutputHelper::getResult($category);
    }

    /**
     * @OA\Delete(
     *     path="/category/{id}",
     *     summary="Удаляет категорию",
     *     tags={"category"},
     *     description="Метод для удаления категории",
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
        $category = \app\models\Category::find()->where(['id' => $id])->one();
        if (!$category) {
            return JsonOutputHelper::getError('Категория не найдена');
        }

        $catalog = \app\models\Catalog::find()->where(['id' => $category->catalog_id])->one();
        if (!$catalog) {
            return JsonOutputHelper::getError('Категория  не найдена');
        }

        if ($catalog->user_id != $me->id && $me->role_id != 1) {
            return JsonOutputHelper::getError('Категория  не принадлежит пользователю');
        }
        $catalog->setAndSaveUpdate();
        $category->delete();
        return JsonOutputHelper::getResult(true);
    }

    /**
     * @OA\Get(
     *     path="/category/{id}",
     *     summary="Возвращает категорию",
     *     tags={"category"},
     *     description="Метод для получения категории",
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
    public function actionView($id) {
        $me = \Yii::$app->user->identity;
        $category = \app\models\Category::find()->where(['id' => $id])->with(['attachedCategories'])->one();
        if (!$category) {
            return JsonOutputHelper::getError('Категория не найдена');
        }

        $catalog = \app\models\Catalog::find()->where(['id' => $category->catalog_id])->one();
        if ($catalog->user_id != $me->id && $me->role_id != 1) {
            return JsonOutputHelper::getError('Категория  не принадлежит пользователю');
        }

        $models = array($category);
        $data = $this->prepareData($models, 0, 0, false, true);
        return JsonOutputHelper::getResult($data['catalogFolders'][0]);
    }

     /**
      * @OA\Put(
      *     path="/category/{id}",
      *     summary="Обновляет категорию по id",
      *     tags={"category"},
      *     security={{"bearerAuth":{}}},
      *     description="Обновляет имя и параметры категории",
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
      *                     property="categoryName",
      *                     description="categoryName",
      *                     type="string",
      *                 ),
      *                @OA\Property(
      *                     property="isFixPrice",
      *                     description="isFixPrice",
      *                     type="boolean",
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
          $category = \app\models\Category::find()->where(['id' => $id])->one();
          if (!$category ){
               return JsonOutputHelper::getError('Категория не найдена');
          }

          $category->title = $params['categoryName'];
          $boolval = filter_var($params['isFixPrice'], FILTER_VALIDATE_BOOLEAN);
          if ($boolval) {
               $category->isFixPrice = 1;
          } else {
               $category->isFixPrice = 0;
          }

          $category->save();

          $category = \app\models\Category::find()->where(['id' => $id])->one();
          $models = array($category);
          $data = $this->prepareData($models, 0, 0, false, true);
          return JsonOutputHelper::getResult($data['catalogFolders'][0]);
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
