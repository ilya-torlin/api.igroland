<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;
use app\components\ImageSaveHelper;
/**
 * MVC controller that handles "users/*" urls.
 */
class UserController extends ActiveController {

    public $modelClass = 'app\models\User';
    
    public function checkAccess($action, $model = null, $params = []) {
        
    }
 /**
     * @OAS\Get(
     *     path="/user/me",
     *     summary="Возвращает все данные авторизованного пользователя",
     *     tags={"user"},
     *     description="Метод для для получения данных пользоватлея",
     *     security={{"bearerAuth":{}}},        
     *     @OAS\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *     @OAS\Response(
     *         response=401,
     *         description="Необходимо отправить авторизационный токен"
     *     ),
     * )
     */
    public function actionMe() {
        $me = \Yii::$app->user->identity;
        //$image = \app\models\Image::find()->where(['id' => $me->image_id]);
        $model = $me->toArray();
        //$model['photo'] = $image->path;
        return JsonOutputHelper::getResult($model);
    }

    public function actions() {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['view'], $actions['delete']);
        return $actions;
    }
    /**
      * @OA\Post(
      *     path="/user/{id}/saveimage",
      *     summary="Загружает администраторскую галерею для товара",
      *     tags={"user"},
      *     description="",
      *     security={{"bearerAuth":{}}},
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         required=false,
      *         @OA\Schema(
      *             type="integer",
      *         )
      *     ),
      *     @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="multipart/form-data",
      *             @OA\Schema(
      *                type="object",
      *                @OA\Property(
      *                     property="file",
      *                     description="file",
      *                     type="file",
      *                 ),
     *                 @OA\Property(
     *                     property="id",
     *                     description="id",
     *                     type="string",
     *                 ),
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="successful operation"
      *     ),

      * )
      */
    public function actionSaveimage($id) {
         $me = \Yii::$app->user->identity;
         if ($me->id != $id) {
              return JsonOutputHelper::getError('Доступно только для себя');
         }

         $params = \Yii::$app->request->post();
         // получаем переданные файлы
         $files = \yii\web\UploadedFile::getInstancesByName($params['id']);
         foreach ($files as $file) {
              $data = ImageSaveHelper::saveFromFile($file);
              if (!$data)
                   return JsonOutputHelper::getError('Ошибка при обработке файлов');;

              $newImage = new \app\models\Image;
              $newImage->path = $data['link'];
              $newImage->save();

              $user = \app\models\User::findOne($id);
              $user->image_id = $newImage->id;
              $user->save();
         }
          return JsonOutputHelper::getResult(array( 'image' => \Yii::$app->params['imageUrls']["ADMIN"] . $data['link']));
    }

    private function prepareDataIndex($models){
        $data = array();
        $idx = 0;
        foreach ($models as $model){
             $item = array(
                  'id' =>  $model['id'],
                  'name' =>  $model['name'],
                  'email' =>  $model['email'],
                  'login' => $model['login'],
                  'photo' => $model['image']['path'],
                  'surname' => $model['surname'],
                  'isActive' => $model['isActive']
             );
           
            array_push($data,  $item);  
            $idx++;            
        }
        return $data;
             
    }
     /**
     * @OA\Get(
     *     path="/user",
     *     summary="Возвращает данные всех пользователей",
     *     tags={"user"},
     *     description="Метод для для получения данных пользоватлей",
     *     security={{"bearerAuth":{}}},
    *      @OA\Parameter(
    *         name="action",
    *         in="query",
    *         required=false,
    *         @OA\Schema(
    *             type="integer",
    *         )
    *     ),
    *      @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Необходимо отправить авторизационный токен"
     *     ),
     * )
     */
    public function actionIndex() {
         $me = \Yii::$app->user->identity;   
         if ($me->role_id != 1){
              return JsonOutputHelper::getError('Только пользователям с ролью Супер пользователя доступно получение списка пользователей');   
         }
         $users = \app\models\User::find();
//         $boolVal = filter_var(\Yii::$app->request->get()['active'], FILTER_VALIDATE_BOOLEAN);
//         if (!$boolVal) {
//              $isActive = 1;
//              $users = $users->where(['isActive' => $isActive]);
//         }
         $users = $users->with(['role','image'])->asArray()->all();
        $data = $this->prepareDataIndex($users);
        return JsonOutputHelper::getResult($data);
    }
     /**
      * @OA\Get(
      *     path="/user/search",
      *     summary="Возвращает данные поиска",
      *     tags={"user"},
      *     description="Метод для для получения искомых данных пользоватлей",
      *     security={{"bearerAuth":{}}},
      *      @OA\Parameter(
      *         name="active",
      *         in="query",
      *         required=false,
      *         @OA\Schema(
      *             type="integer",
      *         )
      *     ),
      *      @OA\Parameter(
      *         name="text",
      *         in="query",
      *         required=false,
      *         @OA\Schema(
      *             type="string",
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="successful operation"
      *     ),
      *     @OA\Response(
      *         response=401,
      *         description="Необходимо отправить авторизационный токен"
      *     ),
      * )
      */
    public function actionSearch() {
          $me = \Yii::$app->user->identity;
          if ($me->role_id != 1){
               return JsonOutputHelper::getError('Только пользователям с ролью Супер пользователя доступно получение списка пользователей');
          }
          $params = \Yii::$app->request->get();
          if (!isset($params['text'])) {
               return JsonOutputHelper::getError('Не заполнен поисковый текст');
          }
          $users = \app\models\User::find()->where(['like', 'email', $params['text']])->orWhere(['like', 'login', $params['text']]);
//          $boolVal = filter_var($params['active'], FILTER_VALIDATE_BOOLEAN);
//          if (!$boolVal) {
//               $isActive = 1;
//               $users = $users->andWhere(['isActive' => $isActive]);
//          }
          $users = $users->with(['role'])->limit(100)->all();
          $data = $this->prepareDataIndex($users);
          return JsonOutputHelper::getResult($data);
     }
	/**
	 * @OA\Delete(
	 *     path="/user/{id}",
	 *     summary="Удаляет пользователя по id",
	 *     tags={"user"},
	 *     description="Метод для удаления пользоватля",
	 *     security={{"bearerAuth":{}}},
	 *    @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=false,
	 *         @OA\Schema(
	 *             type="integer",
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="successful operation"
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Необходимо отправить авторизационный токен"
	 *     ),

	 * )
	 */
    public function actionDelete($id) {
        $me = \Yii::$app->user->identity;   
         if ($me->role_id != 1){
              return JsonOutputHelper::getError('Только пользователям с ролью Супер пользователя доступно получение списка пользователей');   
         }
        $model = \app\models\User::find()->where(['id' => $id])->one();
        $model->delete();
    }
    /**
      * @OA\Post(
      *     path="/user/{id}/setonoff",
      *     summary="Блокирует пользователя",
      *     tags={"user"},
      *     description="Метод для блокировки пользоватлей",
      *     security={{"bearerAuth":{}}},
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         required=false,
      *         @OA\Schema(
      *             type="integer",
      *         )
      *     ),
      *     @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                 @OA\Property(
      *                     property="value",
      *                     description="value",
      *                     type="string",
      *                 )
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="successful operation"
      *     ),
      *     @OA\Response(
      *         response=401,
      *         description="Необходимо отправить авторизационный токен"
      *     ),

      * )
      */
    public function actionSetonoff($id){
         $me = \Yii::$app->user->identity;
         if ($me->role_id != 1){
              return JsonOutputHelper::getError('Только пользователям с ролью Супер пользователя доступно получение списка пользователей');   
         }
         $model = \app\models\User::find()->where(['id' => $id])->one();
         if(!$model)
              return JsonOutputHelper::getError('Пользователь не найден');
         $boolval = filter_var(\Yii::$app->request->post()['value'], FILTER_VALIDATE_BOOLEAN);
         if ($boolval) {
              $model->isActive = 1;
         } else {
              $model->isActive = 0;
         }
         $model->save();
    }
     /**
      * @OA\Put(
      *     path="/user/{id}",
      *     summary="Обновляет данные пользователя",
      *     tags={"user"},
      *     description="Метод для обновления пользоватлей",
      *     security={{"bearerAuth":{}}},
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         required=false,
      *         @OA\Schema(
      *             type="integer",
      *         )
      *     ),
      *     @OA\RequestBody(
      *         description="Input data format",
      *         @OA\MediaType(
      *             mediaType="application/x-www-form-urlencoded",
      *             @OA\Schema(
      *                 type="object",
      *                 @OA\Property(
      *                     property="surname",
      *                     description="surname",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="name",
      *                     description="name",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="patronymic",
      *                     description="patronymic",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="login",
      *                     description="login",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="email",
      *                     description="email",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="phone",
      *                     description="phone",
      *                     type="string",
      *                 ),
      *                  @OA\Property(
      *                     property="site",
      *                     description="site",
      *                     type="string",
      *                 ),
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="successful operation"
      *     ),
      *     @OA\Response(
      *         response=401,
      *         description="Необходимо отправить авторизационный токен"
      *     )
      *
      * )
      */
    public function actionUpdate($id) {

          $params = \Yii::$app->request->post();
          $user = \Yii::$app->user->identity;
          $model = \app\models\User::find()->where(['id' => $id])->one();

          if ($user->role_id != 1)
               throw new \yii\web\ForbiddenHttpException(sprintf('Недостатоно прав для редактирования'));

          $model->setAttributes($params);
          $model->validate();
          \Yii::trace(json_encode($model->getErrors()), __METHOD__);
          $model->save();
     }

    public function actionView($id) {
        $model = \app\models\User::find()->where(['id' => $id])->with(['role'])->one();
        $model = $model->toArray();
        return $model;
    }

    public function actionSave() {

        $params = \Yii::$app->request->post();

        if (!array_key_exists('email', $params)) {
            throw new \yii\web\ForbiddenHttpException(sprintf('Не указан email'));
        }

        if (!array_key_exists('password', $params))
            throw new \yii\web\ForbiddenHttpException(sprintf('Не указан пароль'));

        if (strlen($params['password']) < 5)
            throw new \yii\web\ForbiddenHttpException(sprintf('Пароль не может быть меньше 5 символов'));


        $u = \app\models\User::find()->where([
                    'email' => $params['email']
                ])->one();

        if ($u)
            throw new \yii\web\ForbiddenHttpException(sprintf('Пользователь с таким email уже существует'));

        $me = \Yii::$app->user->identity;
        $user = new \app\models\User;
        $user->role_id = 1;
        $user->email = $params['email'];
        $user->password = \Yii::$app->getSecurity()->generatePasswordHash($params['password']);
        $user->accessToken = $user->password;
        $user->save();
        return $user->id;
    }

    public function behaviors() {

        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];



        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => ['Origin' => ['*']]];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

}
