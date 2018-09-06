<?php

namespace app\controllers;

use yii;
use yii\web\Controller;

class TestController extends Controller {

    public $data = array();

    public function create_csv_file($create_data, $file = null, $col_delimiter = ';', $row_delimiter = "\r\n") {

        if (!is_array($create_data))
            return false;

        if ($file && !is_dir(dirname($file)))
            return false;

        // строка, которая будет записана в csv файл
        $CSV_str = '';

        // перебираем все данные
        foreach ($create_data as $row) {
            $cols = array();

            foreach ($row as $col_val) {
                // строки должны быть в кавычках ""
                // кавычки " внутри строк нужно предварить такой же кавычкой "
                if ($col_val && preg_match('/[",;\r\n]/', $col_val)) {
                    // поправим перенос строки
                    if ($row_delimiter === "\r\n") {
                        $col_val = str_replace("\r\n", '\n', $col_val);
                        $col_val = str_replace("\r", '', $col_val);
                    } elseif ($row_delimiter === "\n") {
                        $col_val = str_replace("\n", '\r', $col_val);
                        $col_val = str_replace("\r\r", '\r', $col_val);
                    }

                    $col_val = str_replace('"', '""', $col_val); // предваряем "
                    $col_val = '"' . $col_val . '"'; // обрамляем в "
                }

                $cols[] = $col_val; // добавляем колонку в данные
            }

            $CSV_str .= implode($col_delimiter, $cols) . $row_delimiter; // добавляем строку в данные
        }

        $CSV_str = rtrim($CSV_str, $row_delimiter);

        // задаем кодировку windows-1251 для строки
        if ($file) {
             //var_dump($CSV_str);
            try {
                $CSV_str = iconv("UTF-8", "cp1251//IGNORE", $CSV_str);
            } catch (Exception $ex) {
                var_dump($CSV_str);
              

            }
            

            // создаем csv файл и записываем в него строку
            $done = file_put_contents($file, $CSV_str);

            return $done ? $CSV_str : false;
        }

        return $CSV_str;
    }
    
    public function prepareProduct($level, $product) {

        $item = array();
        for ($i = 0; $i < $level; $i++) {
            $item[] = '';
        }
        $item[] = $product->title;
        $this->data[] = $item;       
    }

    public function prepareCategory($level, $category) {

        $item = array();
        for ($i = 0; $i < $level; $i++) {
            $item[] = '';
        }
        $item[] = $category->title;
        $this->data[] = $item;
        if ($category->categories) {
            foreach ($category->categories as $childCategory) {
                $this->prepareCategory($level + 1, $childCategory);
            }
        } else {
            $products = \app\models\Product::find()->innerjoin('product_category', 'product_category.product_id = product.id')->where(['product_category.category_id' => $category->id, 'deleted' => 0])->limit(10)->all();
            foreach ($products as $product) {
                $this->prepareProduct($level + 1, $product);
            }
        }
    }

    public function actionIndex() {

        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $level = 0;
        $categroies = \app\models\Category::find()->where(['parent_id' => null, 'supplier_id' => 1])->all();

        foreach ($categroies as $category) {
            $this->prepareCategory(0, $category);
        }
        echo "<pre>";
        // var_dump($this->data);
        echo "</pre>";
        $this->create_csv_file($this->data, '/home/admin/web/igroland-api.praweb.ru/public_html/data/export/1.csv');
    }

}
