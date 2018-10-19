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
            $brand_id = $this->findBrandByName("Игролэнд");
            $csvFile = file($supplier->link);
            var_dump($supplier->link);
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
                 
                    $fileImg = array('http://igroland-api.praweb.ru/data/img_new/' . $item[1] . '.jpg', 'http://igroland-api.praweb.ru/data/img_new/' . $item[1] . '.JPG');
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
                        "brand_id" => $brand_id,
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

                    //Формируем noimages
                    $product = \app\models\Product::find()->where(['sku' => $a['sku'], 'supplier_id' => 5])->one();
                    if ($product) {
                        //Картинки у товара есть
                        if (count($product->productImages) > 0) {
                            \app\models\Noimages::deleteAll(['articul' => $a['sku']]);
                        } else {
                            $noimageCount = \app\models\Noimages::find()->where(['articul' => $a['sku']])->count();
                            if (!$noimageCount) {
                                $noimage = new \app\models\Noimages();
                                $noimage->articul = $a['sku'];
                                $noimage->save();
                            }
                        }
                    }
                } catch (\Exception $e) {

                    return $this->getError('Ошибка при сохранении товара ' . $key . ' - ' . $e->getMessage() . ' line- ' . $e->getLine());
                }
            }
            //Создаем файл noimages



            $filename = \Yii::$app->params['path']['noimagePath'] . 'noimages-' . date('ymd', time() - 24 * 60 * 60) . '.csv';
             
            if (!file_exists($filename)) {
                $targetDate = date('Y-m-d 00:00:00', time() - 2 * 24 * 60 * 60);
                $items = \app\models\Noimages::find();
                $items = $items->where(['<','date',  $targetDate]);
                $items = $items->select('articul')->asArray()->column();
                $data = implode(PHP_EOL, $items);
                file_put_contents($filename, $data);
            }
            
            if ($saved < 1000){
                 return $this->getError('Что то пошло не так, сохранено слишком мало товаров' );
            }

            return  $this->getResult('Найдено товаров ' . $count . ' сохранено ' . $saved);
        } catch (\Exception $e) {
            return $this->getError('Ошибка при чтении файла импорта ' . $e->getMessage());
        }
    }

}
