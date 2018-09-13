<?php

namespace app\components\import\importer;

// класс генерации текстовых полей части отчета
class IgrolandImporter extends BaseImporter implements \app\components\import\ImporterInterface {

    private function prepareDate($csvFile) {
        $data = [];
        $notFullLine = '';
        //Достаем инфомрацию из файла, преобразуем кодировку
        foreach ($csvFile as $line) {
            $s = iconv("cp1251", "UTF-8", $line);
            $s = str_replace('"', '', $s);
            $s = str_replace('/', '', $s);
            $s = str_replace('\\', '', $s);
            $elem = mb_substr($s, -3, 1);

            if (mb_substr($s, -3, 1) != ';') {
                $notFullLine = mb_substr($s, 0, -2) . ' ';
                continue;
            }

            $data[] = str_getcsv($notFullLine . $s, ';');
            $notFullLine = '';
        }
        return $data;
    }

    private function parseItem($item) {
        
    }

    public function engine($supplier) {
        try {
            $csvFile = file($supplier->link);
            $data = $this->prepareDate($csvFile);
            $count = 0;
            $saved = 0;
            foreach ($data as $key => $item) {
                $count++;
                try {
                    $hit = 0;
                    if (array_key_exists(9, $item)) {
                        if ($item[9] != '0')
                            $hit = 1;
                    }

                    $category_id = $this->findCategoryByName($item[0], $supplier);
                    //Если категория не найдена
                    if (!$category_id) {
                        $newCategory = array();
                        $newCategory['title'] = $item[0];
                        $newCategory['supplier_id'] = (int) $supplier->id;
                        $newCategory['catalog_id'] = (int) $supplier->id;
                        $newCategory['parent_id'] = null;
                        $newCategory['external_id'] = mt_rand();
                        $category_id = $this->saveCategory($newCategory);
                    }

                    $fileImg = array('http://analyze-it.su/images/img_new/' . $item[1] . '.jpg', 'http://analyze-it.su/images/img_new/' . $item[1] . '.JPG');

                    $a = [
                        "price_add" => $supplier->price_add,
                        "supplier_id" => $supplier->id,
                        "sid" => $item[1],
                        "sku" => $item[1],
                        "quantity" => $item[4],
                        "sale" => $item[5],
                        "dateupdate" => "",
                        "category_id" => $category_id,
                        "import_title" => str_replace('/', '', $item[2]),
                        "images" => $fileImg,
                        "amount" => $item[3],
                        "supplier_price" => $item[6] == '' ? $item[3] : $item[6],
                        "pack" => $item[7] == 0 ? 1 : $item[7],
                        "brand" => "Игролэнд",
                        "barcode" => "",
                        "code1c" => "",
                        "depth" => "",
                        "width" => "",
                        "height" => "",
                        "weight" => "",
                        "unit" => "",
                        "certificate" => "",
                        "description" => "",
                        "hit" => $hit
                    ];
                    //var_dump($a);
                    //die();



                    if (empty($a["sku"])) {
                        //Пустой артикул
                        continue;
                    }

                    $saved += $this->saveProduct($a);
                } catch (\Exception $e) {

                    return $this->getError('Ошибка при сохранении товара ' . $key . ' - ' . $e->getMessage() . ' line- ' . $e->getLine());
                }
            }




            return 'Найдено товаров ' . $count . ' сохранено ' . $saved;
        } catch (\Exception $e) {
            return $this->getError('Ошибка при чтении файла импорта ' . $e->getMessage());
        }
    }

}
