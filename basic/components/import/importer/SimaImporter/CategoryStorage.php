<?php

namespace app\components\import\importer\SimaImporter;

use SimaLand\API\BaseObject;
use SimaLand\API\Record;

class CategoryStorage extends BaseObject implements \SimaLand\API\Parser\StorageInterface {
     /**
      * Id поставщика для записи в базу
      *
      * @var
      */
     public $supplierId;
     public $arrayCategory = array();


     //[id] => 41119
     //[sid] => 41119
     //[name] => Блокноты для первых слов
     //[priority] => 0
     //[priority_home] => 0
     //[priority_menu] => 0
     //[is_hidden_in_menu] => 0
     //[path] => 3003.8567.16010.41119
     //[level] => 4
     //[type] => 0
     //[is_adult] => 0
     //[has_loco_slider] => 1
     //[has_design] =>
     //[has_as_main_design] =>
     //[is_item_description_hidden] =>
     //[photo] =>
     //[icon] => https://cdn2.static1-sima-land.com/items/1382458/0/140.jpg
     //[is_leaf] => 1
     //[full_slug] => prazdniki/rozhdenie-rebenka/suveniry/bloknoty-dlya-pervyh-slov

     public function findCategoryByExternalId($id, $catId) {
          $model = \app\models\Category::find()->where(['external_id' => $id, 'catalog_id' => $catId])->one();
          if (!$model)
               return false;
          $model->pre_deleted = 0;
          $model->deleted = 0;
          $model->save();
          return $model->id;
     }
     public function findCategoryByName($name, $catId) {
          $model = \app\models\Category::find()->where(['title' => $name, 'catalog_id' => $catId])->one();
          print_r($model);
          if (!$model)
               return false;
          $model->pre_deleted = 0;
          $model->deleted = 0;
          $model->save();
          return $model->id;
     }
     public function getError($text) {
          return array('success' => false, 'error' => $text, 'data' => '');
     }
     public function getResult($data) {
          return array('success' => true, 'error' => '', 'data' => $data);
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
          if ($newCategory->validate()) {
               $newCategory->save();
               return $newCategory->id;
          } else {
               var_dump($category);
               echo "!!!!!!!!";
               var_dump($newCategory->errors);
               die();
          }
     }

     public function save(Record $record) {
          try {
               /*$newCategory = array();
               $newCategory['title'] = (string) $record->data['name'];
               $newCategory['supplier_id'] = (int) $this->supplierId;
               $newCategory['catalog_id'] = (int) $this->supplierId;
               $newCategory['external_id'] = (int) $record->data['id'];
               */
               //if (in_array('11748',$record->data['trademarks']))
                    //print_r($record->data);
               $parentArray = explode('.', (string) $record->data['path']);
               $currentId =(int) array_pop($parentArray);
               $parentId =(int) array_pop($parentArray);
               //$this->findCategoryByName('Букволенд', $this->supplierId);
               if(in_array($record->data['id'],) ){
                    $this->arrayCategory[] = $record->data['id'];
               }
               /*
               $currentCategory = $this->findCategoryByExternalId($currentId, $this->supplierId);
               $findCategory = \app\models\Category::find()->where(['id' => $currentCategory])->one();
               */
               /*
               $parent_id = $this->findCategoryByExternalId($parentId, $this->supplierId);
               if($parent_id){
                    if(!empty($findCategory->parent_id)){
                         $findCategory->parent_id = $parent_id;
                         $findCategory->save();
                    }
               } */
               /**($parent_id) ? $newCategory['parent_id'] = $parent_id : $newCategory['parent_id'] = null;
               $category_id = $this->findCategoryByName($newCategory['title'], $this->supplierId);
               if(!$category_id){
                    echo print_r($record->data);
                    echo print_r($newCategory);
                    $newId = $this->saveCategory($newCategory);
                    $this->arrayCategory[$record->data['id']] = $newId;
               }
                */
               //echo print_r($record->data);
               //echo print_r($newCategory);
               //return $this->getResult($newCategory);
          } catch (\Exception $e) {
               //echo print_r($e);
               //return $this->getError('Ошибка при сохранении категории ' .  $record->data['name'] . ' - ' . $e->getMessage().' line- '.$e->getLine());
          }

     }
}