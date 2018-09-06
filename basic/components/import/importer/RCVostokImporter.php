<?php

namespace app\components\import\importer;

// класс генерации текстовых полей части отчета
class RCVostokImporter extends BaseImporter implements \app\components\import\ImporterInterface {
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
        file_put_contents(dirname(__FILE__) . "/test.xml", $data);
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
            $categories = $data->catalog->category;
            // $category->catalog_id;  // id каталога
            // $category->supplier_id;   // id производителя
            // $category->parent_id; // id родителя в нашей базе
            // $category->external_id;   // id категории старая
            // $category->title;   // название категории
            $currentArrayCC = [];
            foreach ($categories as $key => $category) {
                try {
                    $newCategory = array();
                    $newCategory['title'] = $category->name->__toString();
                    $newCategory['supplier_id'] = (int) $supplier->id;
                    $newCategory['catalog_id'] = (int) $supplier->id;
                    $newCategory['external_id'] = $category->id->__toString();
                    if (isset($category->parent)) {
                        $parent_id = $this->findCategoryByExternalId($category->parent->__toString(), $supplier);
                        $newCategory['parent_id'] = $parent_id;
                    } else {
                        $newCategory['parent_id'] = null;
                    }

                    $category_id = $this->findCategoryByExternalId($newCategory['external_id'], $supplier);

                    if (!$category_id) {
                        $category_id = $this->saveCategory($newCategory);
                    }
                    $currentArrayCC[$newCategory['external_id']] = $category_id;
                } catch (\Exception $e) {
                    return $this->getError('Ошибка при сохранении категории ' . $key . ' - ' . $e->getMessage() . ' line- ' . $e->getLine());
                }
            }


            // товары
            $goods = $data->products->product;
            // проходимся по товарам
            foreach ($goods as $gkey => $good) {
                try {
                    $hit = 0;

                    // проходим по картинкам
                    $image_array = array();
                    if ($good->images && $good->images->image && $good->images->image[0]) {
                        foreach ($good->images->image as $image) {
                            $image_array[] = (string) $image;
                        }
                    }

                    $brand_id = 0;
                    if (isset($good->brand)) {
                        $brand_id = $this->findBrandByName($good->brand->__toString());
                    }

                    $a = [
                        "price_add" => $supplier->price_add,
                        "supplier_id" => $supplier->id,
                        "sid" => $good->id->__toString(),
                        "sku" => $good->article->__toString(),
                        "quantity" => (int)$good->count,
                        "sale" => 0,
                        "dateupdate" => "",
                        "category_id" => $currentArrayCC[(int) $good->category_id],
                        "import_title" => (string) $good->name,
                        "images" => $image_array,
                        "supplier_price" => (int) $good->price,
                        "pack" => (int) $good->minparty,
                        "brand" => ($brand_id) ? $brand_id : null,
                        "barcode" => "",
                        "code1c" => "",
                        "depth" => "",
                        "unit" => "",
                        "certificate" => "",
                        "description" => (string) $good->description,
                        "hit" => $hit
                    ];

                    if (empty($a["sku"])) {
                        //Пустой артикул
                        continue;
                    }
                    echo 'Обрабатываем товар: ' . $a['import_title'];
                    var_dump($a);
                    die();

                    $this->saveProduct($a);
                } catch (\Exception $e) {
                    return $this->getError('Ошибка при сохранении товара ' . $good->name . ' !!' . $value . '!! ' . $e->getMessage() . ' line- ' . $e->getLine());
                }
            }

            return $this->getResult($data->xml_catalog->shop);
        } catch (\Exception $e) {
            return $this->getError('Ошибка при чтении файла импорта ' . $e->getMessage());
        }
    }

}
