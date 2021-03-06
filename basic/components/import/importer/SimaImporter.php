<?php


namespace app\components\import\importer;

class SimaImporter extends BaseImporter implements \app\components\import\ImporterInterface{
     private const LOGIN = 'suvenir1@yandex.ru';
     private const PASSWORD = '758311';

     public function engine($supplier)
     {
          $currentPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "SimaImporter" . DIRECTORY_SEPARATOR;
          if (!file_exists($currentPath)) {
               mkdir($currentPath);
               chmod($currentPath, 0777);
          }
          $client = new \SimaLand\API\Rest\Client([
               'login' => self::LOGIN,
               'password' => self::PASSWORD,
               'pathToken' => $currentPath,
               'baseUrl' => 'https://www.sima-land.ru/api/v3',
          ]);

          $httpClient = new \GuzzleHttp\Client();
          $client->setHttpClient($httpClient);

//          $categoryList = new \SimaLand\API\Entities\CategoryList(
//               $client,
//               [
//                    'countThreads' => 10,
//                    'getParams' => [
//                         'with_adult' => 0,
//                         'expand' => 'trademarks'
//                    ],
//                    'fields' => [
//                         'id',
//                         'name',
//                         'path',
//                         'level',
//                         'trademarks'
//                    ]
//               ]
//          );
//          $categoryStorage = new \SimaLand\API\Parser\Json([
//               'filename' => $currentPath . 'category.txt'
//          ]);
//          $newCategoryStorage = new \app\components\import\importer\SimaImporter\CategoryStorage([
//               'supplierId' => $supplier->id
//          ]);

          $itemList = new \SimaLand\API\Entities\ItemList(
               $client,
               [ 'getParams' => [ 'with_adult' => 0, ] ]
          );
          $itemStorage = new \app\components\import\importer\SimaImporter\ItemStorage([
               'supplierId' => $supplier->id,
               'price_add' => $supplier->price_add,
          ]);
          $parser = new \SimaLand\API\Parser\Parser([
               'metaFilename' => $currentPath . 'parser_meta',
               'iterationCount' => 1000,
          ]);

//          $trademarkList = new \SimaLand\API\Entities\TrademarkList($client, []);
//          $trademarkStorage = new \SimaLand\API\Parser\Json(['filename' => $currentPath . 'trademark.txt']);
          //$parser->addEntity($categoryList, $newCategoryStorage);
          $parser->addEntity($itemList, $itemStorage);
          //$parser->addEntity($trademarkList, $trademarkStorage);
          // Этот метод удалит метаданные, чтоб начать парсинг с самого начала.
          // $parser->reset();
          // Вы можете запустить парсинг с параметром false. В этом случаи метаданные будут игнорироваться.
          // $parser->run(false);
          $parser->reset();
          $parser->run();

          $parseCategoriesArray = array_unique($itemStorage->GetArrayCatogories());
          $linkProductCategoryArray = $itemStorage->GetArrayCategoryProduct();
          file_put_contents($currentPath . 'categories.txt', json_encode($parseCategoriesArray));
          file_put_contents($currentPath . 'categories-products.txt', json_encode($linkProductCategoryArray));

//          $parseCategoriesArray = array_unique(json_decode(file_get_contents($currentPath . 'categories.txt'), true));
//          $linkProductCategoryArray = json_decode(file_get_contents($currentPath . 'categories-products.txt'), true);

          $linkCategoriesIdExternalId = $this->engineCategories($parseCategoriesArray, $supplier);

          file_put_contents($currentPath . 'categories-ext.txt', json_encode($linkCategoriesIdExternalId));

          $this->linkProductCategory($linkProductCategoryArray,$linkCategoriesIdExternalId);

          $this->linkCategoryParents($parseCategoriesArray,$linkCategoriesIdExternalId);
     }

     private function linkProductCategory($ProductCategoryArray, $CategoriesIdExternalId){
          if (empty($ProductCategoryArray) || empty($CategoriesIdExternalId))
               return;

          foreach ($ProductCategoryArray as $item){
               var_dump($item);
               $model = new \app\models\ProductCategory();
               $model->product_id = $item['product'];
               $model->category_id = $CategoriesIdExternalId[$item['category']];
               if ($model->validate())
                    $model->save();
          }
     }

     private function linkCategoryParents($categories, $CategoriesIdExternalId){
          foreach ($categories as $exterId) {
               // базовый путь для импортера
               $curl = curl_init("https://www.sima-land.ru/api/v3/category/{$exterId}/");
               curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
               curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
               $json = curl_exec($curl);
               curl_close($curl);

               $record = json_decode($json);

               $newCategory = \app\models\Category::find()->where(['id' => $CategoriesIdExternalId[$exterId]])->one();
               $parentArray = explode('.', (string) $record->path);
               $currentId =(int) array_pop($parentArray);
               $parentId =(int) array_pop($parentArray);
               if(isset($CategoriesIdExternalId[$parentId]))
                    $parent_id = $CategoriesIdExternalId[$parentId];
               if(isset($parent_id)) {
                    $newCategory->parent_id = $parent_id;
                    $newCategory->save();
               }
          }
     }

     private function engineCategories($categories,$supplier)
     {
          $currentArrayCC = [];
          foreach ($categories as $id){
               // базовый путь для импортера
               $curl = curl_init("https://www.sima-land.ru/api/v3/category/{$id}/");
               curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
               curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
               $json = curl_exec($curl);
               curl_close($curl);

               $record = json_decode($json);

               $newCategory = array();
               $newCategory['title'] = (string) $record->name;
               $newCategory['supplier_id'] = (int) $supplier->id;
               $newCategory['catalog_id'] = (int) $supplier->id;
               $newCategory['external_id'] = (int) $record->id;
               $parentArray = explode('.', (string) $record->path);
               $currentId =(int) array_pop($parentArray);
               $parentId =(int) array_pop($parentArray);

               $parent_id = $this->findCategoryByExternalId((int) $parentId, $supplier);
               ($parent_id) ? $newCategory['parent_id'] = $parent_id : $newCategory['parent_id'] = null;

               $category_id = $this->findCategoryByExternalId( (int) $record->id, $supplier);
               if(!$category_id){
                    $category_id = $this->saveCategory($newCategory);
               }
               $currentArrayCC[$newCategory['external_id']] = $category_id;
               //echo "работа с категорией " . $record->name;
          }
          return $currentArrayCC;
     }
}