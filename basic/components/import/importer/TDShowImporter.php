<?php

namespace app\components\import\importer;

// класс генерации текстовых полей части отчета
class TDShowImporter extends BaseImporter implements \app\components\import\ImporterInterface {

    private $currentArrayCC = array();
    private $supplier = null;

    /*
     * Подготавливает данные полученные с url.
     * получает данные с адреса с помощью cUrl и делает из них xml, после чего
     * возвращает
     * @params $xmlFile - путь до файла или urldecode
     * @return $xml - структуру xml
     */

    private function prepareDate($xmlPath) {
        $data = file_get_contents($xmlPath . '/import.xml');
        $import = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);

        $data = file_get_contents($xmlPath . '/offers.xml');
        $offers = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);

        return ['import' => $import, 'offers' => $offers];
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

    public function engineCategory($category, $parentId) {
        try {
            $newCategory = array();
            $newCategory['title'] = (string) $category->Наименование;
            $newCategory['supplier_id'] = (int) $this->supplier->id;
            $newCategory['catalog_id'] = (int) $this->supplier->id;
            $newCategory['external_1c_id'] = (string) $category->Ид;
            $newCategory['parent_id'] = $parentId;


            $category_id = $this->findCategoryByExternal1cId($newCategory['external_1c_id'], $this->supplier);

            if (!$category_id) {
                $category_id = $this->saveCategory($newCategory);
            }

            $this->currentArrayCC[$newCategory['external_1c_id']] = $category_id;
            if ($category->Группы)
                foreach ($category->Группы->Группа as $key => $category) {
                    $this->engineCategory($category, $category_id);
                }
        } catch (\Exception $e) {
            return $this->getError('Ошибка при сохранении категории ' . $key . ' - ' . $e->getMessage() . ' line- ' . $e->getLine());
        }
    }

    public function engine($supplier) {
        try {
            $xmlPath = $supplier->link;
            $this->supplier = $supplier;
            $data = $this->prepareDate($xmlPath);
            //$newxml = simplexml_load_string($data);
            // категории
            $categories = $data['import']->Классификатор->Группы->Группа->Группы->Группа;
            // $category->catalog_id;  // id каталога
            // $category->supplier_id;   // id производителя
            // $category->parent_id; // id родителя в нашей базе
            // $category->external_id;   // id категории старая
            // $category->title;   // название категории
            //var_dump($categories);

            foreach ($categories as $key => $category) {
                $this->engineCategory($category, null);
            }
            
            //Достанем все нужное offer.xml
            
            $goods = $data['offers']->ПакетПредложений->Предложения->Предложение;
            $offers = array();
            $count = 0;
            $saved = 0;
            foreach ($goods as $gkey => $good) {
                $offers[(string)$good->Ид] = ['price' => (float)$good->Цены->Цена[0]->ЦенаЗаЕдиницу,'quantity' => (int)$good->Количество];            
            }
           

            
            $goods = $data['import']->Каталог->Товары->Товар;
            $count = 0;
            $saved = 0;
            foreach ($goods  as $good) {
                $count++;
                try {
                    $hit = 0;
                    $id = (string)$good->Ид;
                    // проходим по картинкам
                    $image_array = array();
                    if ($good->Картинка){
                         $image_array[] =$xmlPath.'/'. (string)$good->Картинка;
                    }
                    $category_ids = [];
                    if ($good->Группы){
                         foreach ($good->Группы->Ид as $group) {
                           $cat_id = $this->findCategoryByExternal1cId((string)$group, $supplier);
                           if ($cat_id){
                               $category_ids[] = $cat_id;
                           }                         
                         }
                    }
                    
                    $attr = [];
                    if ($good->ЗначенияСвойств)
                      foreach ($good->ЗначенияСвойств->ЗначенияСвойства  as $param) {
                         if ((string)$param->Ид == '6b430a0b-ef6b-11e2-b059-485b390ba44b') {
                             $attr['pack'] = (int)$param->Значение;
                         }
                          if ((string)$param->Ид == '6b430a0a-ef6b-11e2-b059-485b390ba44b') {
                             $attr['min_order'] = (int)$param->Значение;
                         }
                      }   
                    

                    $a = [
                        "price_add" => $supplier->price_add,
                        "supplier_id" => $supplier->id,
                        "sid" => $id,
                        "sku" => (string) $good->Артикул,
                        "quantity" => $offers[$id]['quantity'],
                        "sale" => 0,
                        "dateupdate" => "",
                        "category_ids" => $category_ids,
                        "import_title" => (string) $good->Наименование,
                        "images" => $image_array,
                        "supplier_price" => $offers[$id]['price'],
                        "pack" => $attr['pack']?$attr['pack']:1,
                         "min_order" => $attr['min_order']?$attr['min_order']:1,
                        "brand" => null,
                        "barcode" => (string) $good->Штрихкод,
                        "code1c" => "",
                        "depth" => "",
                        "width" => '',
                        "height" => '',
                        "weight" =>  '',
                        "unit" => "",
                        "certificate" => "",
                        "description" => '',
                        "hit" => $hit
                    ];

                    if (empty($a["sku"])) {
                        $a["sku"] = $id;
                    }
                   // echo 'Обрабатываем товар: ' . $a['import_title'];
                    //var_dump($a);
                    //die();

                    $saved += $this->saveProduct($a);
                } catch (\Exception $e) {
                    return $this->getError('Ошибка при сохранении товара ' . $good->name . ' !!' . $value . '!! ' . $e->getMessage() . ' line- ' . $e->getLine());
                }
            }

            return 'Найдено товаров ' . $count . ' сохранено ' . $saved;
        } catch (\Exception $e) {
            return $this->getError('Ошибка при чтении файла импорта ' . $e->getMessage());
        }
    }

}
