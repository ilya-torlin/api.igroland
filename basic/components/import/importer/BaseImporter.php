<?php

namespace app\components\import\importer;

class BaseImporter implements \app\components\import\ImporterInterface {

    public function calcPrice($product, $basePrice, $price_add) {
        if (isset($product->price_add)) {
            $price_add = $product->price_add;
        }
        $price = $basePrice + ($basePrice * ($price_add / 100));
        return $price;
    }

    public function engine($supplier) {
        
    }

    public function getError($text) {
        return array('success' => false, 'error' => $text, 'data' => '');
    }

    public function getResult($data) {
        return array('success' => true, 'error' => '', 'data' => $data);
    }

    public function findCategoryByName($name, $supplier) {
        $model = \app\models\Category::find()->where(['title' => $name, 'catalog_id' => $supplier->id])->one();
        if (!$model)
            return false;
        $model->pre_deleted = 0;
        $model->deleted = 0;
        $model->save();
        return $model->id;
    }

    public function findBrandByName($name) {
        $model = \app\models\Brand::find()->where(['title' => $name])->one();
        if (!$model){
            $model = new  \app\models\Brand();
            $model->title = $name;
            $model->save();
        }
           

        return $model->id;
    }

    public function findCategoryByExternalId($id, $supplier) {
        $model = \app\models\Category::find()->where(['external_id' => $id, 'catalog_id' => $supplier->id])->one();
        if (!$model)
            return false;
        $model->pre_deleted = 0;
        $model->deleted = 0;
        $model->save();
        return $model->id;
    }
     public function findCategoryByExternal1cId($id, $supplier) {
        $model = \app\models\Category::find()->where(['external_1c_id' => $id, 'catalog_id' => $supplier->id])->one();
        if (!$model)
            return false;
        $model->pre_deleted = 0;
        $model->deleted = 0;
        $model->save();
        return $model->id;
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
           // die();
        }
        }

        if ($model->title == $model->import_title) {
            $model->title = $a['import_title'];
            $model->import_title = $a['import_title'];
        }
        if (isset($a['amount'])){
            $model->price = $a['amount'];
            unset($a['amount']);
        } else {
          $model->price = $this->calcPrice($model, $a['supplier_price'], $a['price_add']);  
        }
        
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
            $result = 1;
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
            
            if ($productImage){
                $filename = \Yii::$app->params['path']['basePath']. $productImage->image->path;               
                if (!file_exists($filename)) $productImage = false;
                 if (filesize($filename) < 1024)  { 
                     //var_dump($productImage->image->id);
                     $productImage->delete();
                     $productImage = false;
                 }
                
                
                
                
            }
            
            

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

        if (isset($a['category_ids'])){
             $parentIds = $a['category_ids'];
        } else {
             $parentIds = array($a['category_id']);
        }
       

        \app\models\ProductCategory::deleteAll([
            'AND', 'product_id = :attribute_2', [
                'NOT IN', 'category_id',
                $parentIds
            ]
                ], [
            ':attribute_2' => $model->id
        ]);


        foreach ($parentIds as $pid) {
            $cat = \app\models\ProductCategory::find()->where(['category_id' => $pid, 'product_id' => $model->id])->one();
            if (!$cat) {
                $cat = new \app\models\ProductCategory();
                $cat->category_id = $pid;
                $cat->product_id = $model->id;
                $cat->save();
            }
        }
        //var_dump($model->id);
        //die();
        return $result;
    }

    // $category->catalog_id;  // id каталога
    // $category->supplier_id;   // id производителя
    // $category->parent_id; // id родителя в нашей базе
    // $category->external_id;   // id категории старая
    // $category->title;   // название категории
    public function saveCategory($category) {
        if (array_key_exists('external_id', $category)){
             $newCategory = \app\models\Category::find()->where(['external_id' => $category['external_id'], 'catalog_id' => $category['supplier_id']])->one();
        } elseif (array_key_exists('external_1c_id', $category)){ 
            $newCategory = \app\models\Category::find()->where(['external_1c_id' => $category['external_1c_id'], 'catalog_id' => $category['supplier_id']])->one();
        }
       
        if (!$newCategory) {
            $newCategory = new \app\models\Category();
        }

        $newCategory->attributes = $category;
        $newCategory->deleted = 0;
        $newCategory->pre_deleted = 0;
        if ($newCategory->validate()) {
            $newCategory->save();
        } else {
            var_dump($category);
            echo "!!!!!!!!";
            var_dump($newCategory->errors);
            die();
        }

        return $newCategory->id;
    }

}
