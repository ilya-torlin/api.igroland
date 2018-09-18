<?php
namespace app\components\import\importer\SimaImporter;

use SimaLand\API\BaseObject;
use SimaLand\API\Record;

class ItemStorage extends BaseObject implements \SimaLand\API\Parser\StorageInterface {
     public $supplierId;
     private $arrayCategoryProduct = array();
     private $arrayCatogories = array();

     public function GetArrayCatogories(){
          return $this->arrayCatogories;
     }

     public function GetArrayCategoryProduct(){
          return $this->arrayCategoryProduct;
     }

     public function saveProduct($a) {
          $result = 0;

          ini_set('display_errors', 'On');
          error_reporting(E_ALL);
          $model = \app\models\Product::find()->where(['sid' => $a['sid'], 'supplier_id' => $a['supplier_id']])->one();

          if (!$model) {
               $model = new \app\models\Product();
               $model->title = $a['import_title'];
          } else {
               if ($model->pre_deleted == 0) {
                    var_dump($a);
                    echo 'Товар уже обновляли';
                    die();
               }
          }

          if ($model->title == $model->import_title) {
               $model->title = $a['import_title'];
               $model->import_title = $a['import_title'];
          }

          $model->price = $this->calcPrice($model, $a['supplier_price'], $a['price_add']);
          unset($a['price_add']);
          $model->attributes = $a;
          $model->pre_deleted = 0;
          $model->deleted = 0;

          $model->updated_at = date('Y-m-d h:i:s');
          if ($model->validate()) {
               if (!$model->save()) {
                    var_dump($a);
                    echo 'Товар не удалось сохранить';
                    die();
               }
               $result = $model->id;
          } else {
               echo "!!!!!!!!";
               var_dump($model->errors);
               die();
          }

          $usedImageArray = array();
          foreach ($a['images'] as $image) {
               $imageNameArr = explode('/', $image);
               $imageName = array_pop($imageNameArr);
               if (!$imageName)
                    continue;
               $productImage = \app\models\ProductImage::find()->leftJoin('image', 'image.id = product_image.image_id')
                    ->where(['product_image.product_id' => $model->id])
                    ->andWhere(['like', 'image.path', $imageName])
                    ->one();

               if (!$productImage) {

                    $image_params = \app\components\ImageSaveHelper::saveFromAutoDetect($image);
                    if (!$image_params)
                         continue;


                    $imageObj = new \app\models\Image();
                    $imageObj->path = $image_params['link'];
                    $imageObj->save();


                    $productImage = new \app\models\ProductImage();
                    $productImage->product_id = $model->id;
                    $productImage->image_id = $imageObj->id;
                    $productImage->save();
                    //var_dump($image);
                    //die();
               }

               $usedImageArray[] = $productImage->image_id;
          }

          \app\models\ProductImage::deleteAll([
               'AND', 'product_id = :attribute_2', [
                    'NOT IN', 'image_id',
                    $usedImageArray
               ]
          ], [
               ':attribute_2' => $model->id
          ]);

//          if (isset($a['category_ids'])){
//               $parentIds = $a['category_ids'];
//          } else {
//               $parentIds = array($a['category_id']);
//          }
//
//          \app\models\ProductCategory::deleteAll([
//               'AND', 'product_id = :attribute_2', [
//                    'NOT IN', 'category_id',
//                    $parentIds
//               ]
//          ], [
//               ':attribute_2' => $model->id
//          ]);
//
//          foreach ($parentIds as $pid) {
//               $cat = \app\models\ProductCategory::find()->where(['category_id' => $pid, 'product_id' => $model->id])->one();
//               if (!$cat) {
//                    $cat = new \app\models\ProductCategory();
//                    $cat->category_id = $pid;
//                    $cat->product_id = $model->id;
//                    $cat->save();
//               }
//          }
          //var_dump($model->id);
          //die();
          return $result;
     }

     /**
      * @param Record $record
      */
     public function save(Record $record) {
          try {
               if(empty($record->data['trademark_id']))
                    return;

               $trades = explode(',', $record->data['trademark_id']);
               //var_dump($trades);
               if(in_array(11748, $trades)){
                    $this->arrayCatogories[] = $record->data['category_id'];
                    $this->arrayCategoryProduct[] = array( 'product' => $record->data['id'], 'category' => $record->data['category_id'] );

                    $image_array = array();
                    $name = basename($record->data["img"]);
                    foreach( $record->data["photos"] as $image) {
                         $image_array[] = (string) $image['url_part'] . $name;
                    }
                    $a = [
                         "supplier_id" => $this->supplierId,
                         "sid" => (string) $record->data['sid'],
                         "sku" => (string) $record->data["sid"],
                         "quantity" => (isset($record->data['balance'])) ? (int) $record->data['balance'] : 0,
                         "sale" => 0,
                         "dateupdate" => "",
                         "category_id" => null,
                         "import_title" => (string) $record->data['name'],
                         "images" => $image_array,
                         "amount" => (string) $record->data['price'],
                         "supplier_price" => (string) $record->data['price'],
                         "pack" => (int) $record->data['minimum_order_quantity'],
                         "brand" => null,
                         "barcode" => "",
                         "code1c" => "",
                         "depth" => "",
                         "width" => "",
                         "height" => "",
                         "weight" => "",
                         "unit" => "",
                         "certificate" => "",
                         "description" => "",
                         "hit" => (int) $record->data['is_hit']
                    ];

                    if (empty($a["sku"])) {
                         //Пустой артикул
                         return;
                    }
                    var_dump($a);
                    echo 'Обрабатываем товар: ' . $a['import_title'];
                    //$this->saveProduct($a);
               }
               //return $this->getResult($newCategory);
          } catch (\Exception $e) {
               print_r($e->getMessage());
               //return $this->getError('Ошибка при сохранении категории ' .  $record->data['name'] . ' - ' . $e->getMessage().' line- '.$e->getLine());
          }

     }

}