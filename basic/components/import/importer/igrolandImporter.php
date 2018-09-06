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

    private function parseItem($item){
        
    }
    
    public function engine($supplier) {
        try {
            $csvFile = file($supplier->link);
            $data = $this->prepareDate($csvFile);
            foreach ($data as $key => $item) {
                try {
                    $hit = 0;
                    if (array_key_exists(9, $item)) {
                        if ($item[9] != '0')
                            $hit = 1;
                    }
                   
                    $category_id = $this->findCategoryByName($item[0], $supplier);
                    //Если категория не найдена
                    if (!$category_id){
                        continue;
                    }
                    
                      $fileImg = '/images/img_new/'.$item[1].'.jpg';
                    
                    $a = [                        
                        "supplier_id" => $supplier->id,
                        "sid" => $item[1],
                        "sku" => $item[1],
                        "quantity" => $item[4],
                        "sale" => $item[5],
                        "dateupdate" => "",
                        "category_id" => $category_id,
                        "import_title" => str_replace('/', '', $item[2]),
                        "images" => array($fileImg),
                        "amount" => $item[3],
                        "supplier_price" => $item[6],
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


                    if (empty($a["sku"])) {
                        //Пустой артикул
                        continue;
                    }
                   
                    $this->saveProduct($a);
                } catch (\Exception $e) {
                    
                     return $this->getError('Ошибка при сохранении товара ' . $key . ' - ' . $e->getMessage().' line- '.$e->getLine());
                  
                }
            }




            return $this->getResult($data[0]);
        } catch (\Exception $e) {
            return $this->getError('Ошибка при чтении файла импорта ' . $e->getMessage());
        }
    }

}
