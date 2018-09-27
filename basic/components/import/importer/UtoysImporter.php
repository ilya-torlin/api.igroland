<?php

namespace app\components\import\importer;

class UtoysImporter extends BaseImporter implements \app\components\import\ImporterInterface {
    /*
     * Подготавливает данные полученные с url.
     * получает данные с адреса с помощью cUrl и делает из них xml, после чего
     * возвращает
     * @params $xmlFile - путь до файла или urldecode
     * @return $xml - структуру xml
     */

    private function curl_download($url, $file) {
        $dest_file = fopen($file, "w");
        $resource = curl_init();
        curl_setopt($resource, CURLOPT_URL, $url);
        curl_setopt($resource, CURLOPT_FILE, $dest_file);
        curl_setopt($resource, CURLOPT_HEADER, 0);
        $result = curl_exec($resource);
        $http_code = curl_getinfo($resource, CURLINFO_HTTP_CODE);
        curl_close($resource);
        fclose($dest_file);
        if (!$result)
            return false;
        if ($http_code != 200)
            return false;
        return true;
    }

    private function prepareDate($xmlFile) {
        $date = date('dmY');
        $link = 'http://analyze-it.su/utoy/' . $date . '.zip';
        $fileName = \Yii::$app->params['path']['tmpPath'] . '/utoys.zip';
        if (file_exists($fileName))         unlink($fileName);
        if (!$this->curl_download($link, $fileName)) {
            if (file_exists($fileName))         unlink($fileName);
            $date = date('dmY', time() - 24 * 60 * 60);
            $link = 'http://analyze-it.su/utoy/' . $date . '.zip';
            echo $link;
            $this->curl_download($link, $fileName);
        }

        $zip = new \ZipArchive;
        if ($zip->open($fileName) === TRUE) {
            $zip->extractTo(\Yii::$app->params['path']['tmpPath']);
            $zip->close();
            unlink($fileName);
        } else {
            echo 'ошибка при распаковке zip';
            die();
        }
        
        $xmlMain = simplexml_load_file(\Yii::$app->params['path']['tmpPath'].'/'.$date.'.xml', 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
       


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
       
        return array('xml' => $xml,'xmlMain' => $xmlMain);
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
    
    public function prepareCategoryId($strid){
      $strid =  str_replace('ТЛ', '', $strid);
      return (int)$strid;
    }
    

    public function engine($supplier) {
        try {
            $xmlFile = $supplier->link;
            $data = $this->prepareDate($xmlFile);
            
            //$newxml = simplexml_load_string($data);
            // категории
            $categories = $data['xmlMain']->shop->categories->category;
            ;
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
                    $newCategory['title'] = trim($category->__toString());
                    $newCategory['supplier_id'] = (int) $supplier->id;
                    $newCategory['catalog_id'] = (int) $supplier->id;
                    $newCategory['external_id'] = $this->prepareCategoryId($category['id']);
                    
                    if ($category['parentId']){
                         $parent_id = $this->findCategoryByExternalId($this->prepareCategoryId($category['parentId']), $supplier);
                    } else {
                        $parent_id = null;
                    }
                    $newCategory['parent_id'] = $parent_id;
                    $category_id = $this->saveCategory($newCategory);
                    
                    $currentArrayCC[$newCategory['external_id']] = $category_id;
                } catch (\Exception $e) {
                    return $this->getError('Ошибка при сохранении категории ' . $key . ' - ' . $e->getMessage() . ' line- ' . $e->getLine());
                }
            }
            
 
            // Достаем картинки товаров
            $pictures = array();
            $goods = isset($data['xml']->xml_catalog) ? $data['xml']->xml_catalog->shop->offers->offer : $data['xml']->shop->offers->offer;
            // проходимся по товарам
            foreach ($goods as $gkey => $good) {
                $art='';
                foreach ($good->param as $goodParam) {
                  $goodAttr = $goodParam->attributes();
                  foreach ($goodAttr as $key => $value) {                    
                     
                      if ((string) $value === 'КодНоменклатуры') {
                        $art =  (string)$goodParam;
                     }
                   
                     
                     
                    
                  }


               }
                $pictures[$art] = (string)$good->picture;
            }
           
            
            
            
            
            
            
             // товары
         $goods = isset($data['xmlMain']->xml_catalog) ? $data['xmlMain']->xml_catalog->shop->offers->offer : $data['xmlMain']->shop->offers->offer;
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
                  //var_dump($goodAttr);
                  foreach ($goodAttr as $key => $value) {                    
                     if ((string) $value === 'Бренд') {
                        $currentAttr['brand'] = (string) $goodParam;
                     }
                     if ((string) $value === 'Количество в коробке') {
                        $currentAttr['pack'] = (int) $goodParam;
                     }
                     if ((string) $value === 'Ширина упаковки, см') {
                        $currentAttr['width'] = (int) $goodParam;
                     }
                     if ((string) $value === 'Высота упаковки, см') {
                        $currentAttr['height'] = (int) $goodParam;
                     }
                      if ((string) $value === 'КодНоменклатуры') {
                        $currentAttr['articul'] =(string)$goodParam;
                     }
                   
                     
                     
                    
                  }


               }

               // проходим по картинкам
           
               if (isset($currentAttr['brand'])) {
                  $brand_id = $this->findBrandByName($currentAttr['brand']);
               }
               
               $image_array = array();
               if (array_key_exists($currentAttr["articul"], $pictures)){
                   $image_array[]= $pictures[$currentAttr["articul"]];
               }
               if (empty($good->categoryId)){
                   echo 'Пустой categoryId';
                  continue;
               }

               $a = [
                   "price_add" => $supplier->price_add,
                   "supplier_id" => $supplier->id,
                   "sid" => $currentAttr["id"],
                   "sku" => $currentAttr["articul"],
                   "quantity" => (int)$good->quantity,
                   "sale" => 0,
                   "dateupdate" => "",
                   "category_id" => $currentArrayCC[$this->prepareCategoryId($good->categoryId)],
                   "import_title" => (string) $good->name,
                   "images" => $image_array,
                   "supplier_price" => (float)$good->price,
                   "pack" => 1, //$currentAttr['pack'],
                   "brand_id" => ($brand_id) ? $brand_id : null,
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
                   echo 'Пустой sku';
                   die();
                   continue;
                   
               }
               echo $count.'|';
               $saved+=$this->saveProduct($a);
            } catch (\Exception $e) {
               return $this->getError('Ошибка при сохранении товара ' . $good->name . ' !!'. $value .'!! ' . $e->getMessage().' line- '.$e->getLine());
            }
         }
            
            
            
            
            
            
            
            
            
            
            
            

           return 'Найдено товаров ' . $count . ' сохранено ' . $saved;
        } catch (\Exception $e) {
            return $this->getError('Ошибка при чтении файла импорта ' . $e->getMessage().' line- '.$e->getLine());
        }
    }

}
