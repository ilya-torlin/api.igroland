<?php


namespace app\components\import\importer;

class SimaImporter extends BaseImporter implements \app\components\import\ImporterInterface{
     private const LOGIN = 'suvenir1@yandex.ru';
     private const PASSWORD = '758311';

     public function getDataFromServer(){
          // Путь до директории с файлами.
          $currentPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "SimaImporter" . DIRECTORY_SEPARATOR;
          //return $currentPath;
          // Если нет директории, создаем ее.
          if (!file_exists($currentPath)) {
               mkdir($currentPath);
               chmod($currentPath, 0777);
          }
          // Создаем API клиент. Обязательно указать логин и пароль.
          // Клиент при первом обращении получает токен, который сохраняет по указанному пути.
          $client = new \SimaLand\API\Rest\Client([
               'login' => self::LOGIN,
               'password' => self::PASSWORD,
               // Необязательные параметры.
               // Путь до токена.
               'pathToken' => $currentPath,
               // Базовый url API sima-land.ru.
               'baseUrl' => 'https://www.sima-land.ru/api/v3',
          ]);
          // Добавляем для работы клиента HTTP клиент.
          // HTTP клиент должен реализовать интерфейс \GuzzleHttp\ClientInterface.
          $httpClient = new \GuzzleHttp\Client();
          $client->setHttpClient($httpClient);
          // Создаем объект логгер. Для логирование работы парсера.
          // Логгер должен реализовать интерфейс \Psr\Log\LoggerInterface.
          // По умолчанию весь лог отправляется в php://output.
          $logger = new \Monolog\Logger('SimaParser');
          $logger->pushHandler(new \Monolog\Handler\StreamHandler($currentPath . "parser.log"));
          // Логгер можно добавить с помощью метода setLogger
          // или передать параметром в конструктор API клиента new Client(['logger' => $logger]).
          $client->setLogger($logger);

          // Создаем объекты сущностей и место хронения данных.
          // Категории.
          // Передадим логгер этому объекту в конструкторе.
          // Парсер может одновременно обращаться к API в несколько потоков.
          // Существует лимит, 250 запросов к API за 10 секунд.
          $categoryList = new \SimaLand\API\Entities\CategoryList(
               $client,
               [
                    'logger' => $logger,
                    // Необязательные свойства.
                    // Установим 10 потоков. По умолчанию парсер работает в 5 потоков.
                    'countThreads' => 10,
                    // Можем добавить GET параметры к запросу API.
                    // Например, будем получать категории, включая категории 18+.
                    'getParams' => [
                         'with_adult' => 0,
                         'expand' => 'trademarks'
                    ],
                    // Массив полей, которые нужно сохранять из данной сущности.
                    // По-умолчанию сохраняются все поля сущности.
                    'fields' => [
                         'id',
                         'name',
                         'path',
                         'level',
                         'trademarks'
                    ]
               ]
          );
          // Хранение данных атрибутов
          // Вы можете реализовать свой класс хранения данных, который будет сохранять в MySQL, PostgresQL и т. п..
          // Этот класс должен реализовать интерфейс \SimaLand\API\Parser\StorageInterface.
          // Сейчас мы данные этой сущности сохраним в Json файл.
          $categoryStorage = new \SimaLand\API\Parser\Json([
               'filename' => $currentPath . 'category.txt'
          ]);

          $newCategoryStorage = new \app\components\import\importer\SimaImporter\CategoryStorage();

          // Товары.
          $itemList = new \SimaLand\API\Entities\ItemList(
               $client,
               [
                    'logger' => $logger,
                    // Получим все товары, включая 18+.
                    'getParams' => [
                         'with_adult' => 1
                    ],
                    'fields' => [
                         'id',
                         'name'
                    ]
               ]
          );
          $itemStorage = new \SimaLand\API\Parser\Json(['filename' => $currentPath . 'item.txt']);
          // Загрузка и сохранение всех записей сущностей.
          $parser = new \SimaLand\API\Parser\Parser([
               // Путь до файла с метаданными. Он необходим для продолжения парсинга, если по какой-то причине парсер остановил свою работы.
               'metaFilename' => $currentPath . 'parser_meta',
               // Необязательные свойства.
               // Кол-во итераций после которых сохраняются метаданные.
               'iterationCount' => 1000,
               // Добавим логгер.
               'logger' => $logger
          ]);
          // Добавим в парсер сущности.
          // Метод addEntity() принимает два параметра: список и хранилище.
          // Лист должен наследоваться от класса \SimaLand\API\AbstractList.
          // Хранилище должно реализовывать интерфейс \SimaLand\API\Parser\StorageInterface.
//          $parser->addEntity($categoryList, $categoryStorage);
          $parser->addEntity($categoryList, $newCategoryStorage);
//          $parser->addEntity($attrList, $attrStorage);
//          $parser->addEntity($optionList, $optionStorage);
//          $parser->addEntity($dataTypeList, $dataTypeStorage);
//          $parser->addEntity($materialList, $materialStorage);
//          $parser->addEntity($seriesList, $seriesStorage);
//          $parser->addEntity($countryList, $countryStorage);
//          $parser->addEntity($trademarkList, $trademarkStorage);
          //$parser->addEntity($itemList, $itemStorage);
//          $parser->addEntity($attrItemList, $attrItemStorage);
          // Этот метод удалит метаданные, чтоб начать парсинг с самого начала.
          // $parser->reset();
          // Вы можете запустить парсинг с параметром false. В этом случаи метаданные будут игнорироваться.
          // $parser->run(false);
          // Запускаем процесс парсинга.
          $parser->reset();
          $parser->run();
     }

     public function engine($supplier)
     {
          // базовый путь для импортера
          $currentPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "SimaImporter" . DIRECTORY_SEPARATOR;
          // Если нет директории, создаем ее.
          if (!file_exists($currentPath)) {
               mkdir($currentPath);
               chmod($currentPath, 0777);
          }

          $client = new \SimaLand\API\Rest\Client([
               'login' => self::LOGIN,
               'password' => self::PASSWORD,
               // Необязательные параметры.
               // Путь до токена.
               'pathToken' => $currentPath,
               // Базовый url API sima-land.ru.
               'baseUrl' => 'https://www.sima-land.ru/api/v3',
          ]);

          $httpClient = new \GuzzleHttp\Client();
          $client->setHttpClient($httpClient);

          $categoryList = new \SimaLand\API\Entities\CategoryList(
               $client,
               [
                    'countThreads' => 10,
                    'getParams' => [
                         'with_adult' => 0,
                         'expand' => 'trademarks'
                    ],
                    'fields' => [
                         'id',
                         'name',
                         'path',
                         'level',
                         'trademarks'
                    ]
               ]
          );
          $categoryStorage = new \SimaLand\API\Parser\Json([
               'filename' => $currentPath . 'category.txt'
          ]);
          $newCategoryStorage = new \app\components\import\importer\SimaImporter\CategoryStorage([
               'supplierId' => $supplier->id
          ]);

          $itemList = new \SimaLand\API\Entities\ItemList(
               $client,
               [
                    // Получим все товары, включая 18+.
                    'getParams' => [
                         'with_adult' => 1,
                    ]
               ]
          );
          $itemStorage = new \app\components\import\importer\SimaImporter\ItemStorage([
               'supplierId' => $supplier->id
          ]);
          $parser = new \SimaLand\API\Parser\Parser([
               // Путь до файла с метаданными. Он необходим для продолжения парсинга, если по какой-то причине парсер остановил свою работы.
               'metaFilename' => $currentPath . 'parser_meta',
               // Необязательные свойства.
               // Кол-во итераций после которых сохраняются метаданные.
               'iterationCount' => 1000,
          ]);

          // Добавим в парсер сущности.
          // Метод addEntity() принимает два параметра: список и хранилище.
          // Лист должен наследоваться от класса \SimaLand\API\AbstractList.
          // Хранилище должно реализовывать интерфейс \SimaLand\API\Parser\StorageInterface.
          //$parser->addEntity($categoryList, $newCategoryStorage);
          $parser->addEntity($itemList, $itemStorage);
          //$parser->addEntity($trademarkList, $trademarkStorage);
          // Этот метод удалит метаданные, чтоб начать парсинг с самого начала.
          // $parser->reset();
          // Вы можете запустить парсинг с параметром false. В этом случаи метаданные будут игнорироваться.
          // $parser->run(false);
          // Запускаем процесс парсинга.
          $parser->reset();
          $parser->run();
          var_dump($itemStorage->GetArrayCatogories());
          file_put_contents($currentPath . 'categories.txt', json_encode($itemStorage->GetArrayCatogories()));
          file_put_contents($currentPath . 'categories-ext.txt', json_encode($this->engine1($itemStorage->GetArrayCatogories(),$supplier->id)));
     }

     public function engine1($categories,$supplier)
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
               $newCategory['supplier_id'] = (int) $supplier;
               $newCategory['catalog_id'] = (int) $supplier;
               $newCategory['external_id'] = (int) $record->id;
               $parentArray = explode('.', (string) $record->path);
               $currentId =(int) array_pop($parentArray);
               $parentId =(int) array_pop($parentArray);

               $parent_id = $this->findCategoryByExternalId((int) $parentId, $supplier);
               ($parent_id) ? $newCategory['parent_id'] = $parent_id : $newCategory['parent_id'] = null;

               $category_id = $this->findCategoryByName( (string) $record->name, $supplier);
               if(!$category_id){
                    //$category_id = $this->saveCategory($newCategory);
               }
               $currentArrayCC[$newCategory['external_id']] = $category_id;
          }
          return $currentArrayCC;
     }
}