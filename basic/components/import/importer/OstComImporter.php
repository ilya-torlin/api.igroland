<?php

namespace app\components\import\importer;

// класс генерации текстовых полей части отчета
class OstComImporter extends BaseImporter implements \app\components\import\ImporterInterface {

   /*
      * Подготавливает данные полученные с url.
      * получает данные с адреса с помощью cUrl и делает из них xml, после чего
      * возвращает
      * @params $xmlFile - путь до файла или urldecode
      * @return $xml - структуру xml
   */
   private function prepareDate($xmlFile) {
      $data = '';
      $notFullLine = '';
      //Достаем инфомрацию из файла, преобразуем кодировку
      $ch = curl_init();
      // установка URL и других необходимых параметров
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_URL, $xmlFile);    // get the url contents

      $data = curl_exec($ch); // execute curl request
      curl_close($ch);

      $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
      file_put_contents(dirname(__FILE__) . "/test.xml",$data);
      return $xml;
   }

   /*
      * Обрабатывает импорт и сохраняет товары и категории.
      * для категорий ищет соответстви в базе по id, если находит, то ничего не делает
      * иначе создает такую категорию в базе.
      * ищет по товарам
      * возвращает
      * @params $supplier - поставщик
      * @return $xml - структуру xml
   */
   public function engine($supplier) {
      try {
         $xmlFile = $supplier->link;
         $data = $this->prepareDate($xmlFile);        
         //$newxml = simplexml_load_string($data);
         // категории
         $categories = $data->shop->categories->category;
         // $category->catalog_id;  // id каталога
         // $category->supplier_id;   // id производителя
         // $category->parent_id; // id родителя в нашей базе
         // $category->external_id;   // id категории старая
         // $category->title;   // название категории
         $currentArrayCC = [];
         foreach ($categories as $key => $category) {
            try {
               $attr = $category->attributes();
               $newCategory = array();
               $newCategory['title'] = (string) $category;
               $newCategory['supplier_id'] = (int) $supplier->id;
               $newCategory['catalog_id'] = (int) $supplier->id;
               $newCategory['external_id'] = (string) $category['id'];
               $parent_id = $this->findCategoryByExternalId((int) $category['parentId'], $supplier);
               ($parent_id) ? $newCategory['parent_id'] = $parent_id : $newCategory['parent_id'] = null;


               $category_id = $this->findCategoryByExternalId($newCategory['external_id'], $supplier);
             
               if(!$category_id){
                  $category_id = $this->saveCategory($newCategory);
                    
               }
               $currentArrayCC[$newCategory['external_id']] = $category_id;
            } catch (\Exception $e) {
               return $this->getError('Ошибка при сохранении категории ' . $key . ' - ' . $e->getMessage().' line- '.$e->getLine());
            }
         }

         // товары
         $goods = isset($data->xml_catalog)?$data->xml_catalog->shop->offers->offer:$data->shop->offers->offer;
         // проходимся по товарам
         $count = 0;
         $saved = 0;
         foreach ($goods as $gkey => $good) {
             $count++;
            try {
               $hit = 0;
               $attr = $good->attributes();
               $currentAttr = array();
               // проходимся по атрибутам
               foreach ($attr as $atkey => $value) {
                  $currentAttr[$atkey] = (string) $value;
               }
               
               // проходимся по параметрам товаров
               foreach ($good->param as $goodParam) {
                  $goodAttr = $goodParam->attributes();
                  foreach ($goodAttr as $key => $value) {                    
                     if ((string) $value === 'brand') {
                        $currentAttr['brand'] = (string) $goodParam;
                     }
                     if ((string) $value === 'box') {
                        $currentAttr['pack'] = (int) $goodParam;
                     }
                     if ((string) $value === 'width, cm') {
                        $currentAttr['width'] = (int) $goodParam;
                     }
                     if ((string) $value === 'height, cm') {
                        $currentAttr['height'] = (int) $goodParam;
                     }
                     if ((string) $value === 'Вес брутто 1 шт., кг') {
                        $currentAttr['weight'] = (string) $goodParam;
                         $currentAttr['weight'] = str_replace('.', ',',  $currentAttr['weight']);
                     }
                     
                    
                  }


               }

               // проходим по картинкам
               $image_array = array();
               foreach ($good->picture as $image) {
                  $image_array[] = (string) $image;
               }
               $brand_id = 0;
               if (isset($currentAttr['brand'])) {
                  $brand_id = $this->findBrandByName($currentAttr['brand']);
               }

               $a = [
                   "price_add" => $supplier->price_add,
                   "supplier_id" => $supplier->id,
                   "sid" => $currentAttr["id"],
                   "sku" => $currentAttr["articul"],
                   "quantity" => (int)$good->quantity,
                   "sale" => 0,
                   "dateupdate" => "",
                   "category_id" => $currentArrayCC[(int) $good->categoryId],
                   "import_title" => (string) $good->name,
                   "images" => $image_array,
                   "supplier_price" => (int)$good->price,
                   "pack" => $currentAttr['pack'],
                   "brand" => ($brand_id) ? $brand_id : null,
                   "barcode" => "",
                   "code1c" => "",
                   "depth" => "",
                   "width" => isset($currentAttr['width']) ? (int)$currentAttr['width'] : '',
                   "height" => isset($currentAttr['height']) ? (int)$currentAttr['height'] : '',
                   "weight" => isset($currentAttr['weight']) ? (int)$currentAttr['weight'] : '',
                   "unit" => "",
                   "certificate" => "",
                   "description" => (string)$good->description,
                   "hit" => $hit
               ];

               if (empty($a["sku"])) {
                   //Пустой артикул
                   continue;
               }
               echo 'Обрабатываем товар: ' . $a['import_title'];
              
                $saved+=$this->saveProduct($a);

            } catch (\Exception $e) {
               return $this->getError('Ошибка при сохранении товара ' . $good->name . ' !!'. $value .'!! ' . $e->getMessage().' line- '.$e->getLine());
            }
         }

         return 'Найдено товаров ' . $count . ' сохранено ' . $saved;
      } catch (\Exception $e) {
         return $this->getError('Ошибка при чтении файла импорта ' . $e->getMessage());
      }
   }

}
