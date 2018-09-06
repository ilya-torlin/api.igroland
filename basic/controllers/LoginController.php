<?php

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Igroland Api",
 *         description="",
 *         termsOfService="",
 *         @OA\Contact(
 *             email="sosnin@praweb.ru"
 *         )
 *     ),
 *     @OA\Server(
 *         description="Api host",
 *         url="http://igroland-api.praweb.ru"
 *     )
 * )
 */
  /**
      @OA\SecurityScheme(
      securityScheme="bearerAuth",
      type="http",
      scheme="bearer"
      )
     * */

namespace app\controllers;

use yii;
use yii\rest\ActiveController;
use app\filters\auth\HttpBearerAuth;
use app\components\JsonOutputHelper;

class LoginController extends ActiveController {
    public $modelClass = 'app\models\User';

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
        $behaviors['authenticator']['except'] = ['changepass','restore','login','registration', 'options'];

        return $behaviors;
    }

    public $defaultAction = 'login';

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Авторизует пользователя по логину и паролю",
     *     tags={"login"},
     *     description="Метод для авторизации",
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     description="Email",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     description="Password",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),

     * )
     */
    public function actionLogin() {

        $request = Yii::$app->request;
        if ($request->isOptions) {
            return;
        }
        $params = \Yii::$app->request->post();
        $email = $params['email'];
        $password = $params['password'];

        $u = \app\models\User::find()
                        ->where(['email' => $email])->one();
        
        
        if (!$u){
             $u = \app\models\User::find()
                        ->where(['login' => $email])->one();
        }

        if ($u) {
            if (Yii::$app->getSecurity()->validatePassword($password, $u->password)) {
                $key = Yii::$app->getSecurity()->generateRandomString();
                $u->accessToken = $key;
                $u->validate();
                \Yii::trace(json_encode($u->getErrors()), __METHOD__);
                $u->save();
                $uArr = \app\models\User::find()->where([
                            'id' => $u->id
                        ])->with('role')->asArray()->one();

                return array('error' => false, 'data' => array('user' => $uArr, 'token' => $key, 'id' => $u->id));
            }
            return array('error' => true, 'data' => array('errCode' => 'e403', 'msgDev' => 'Неверный логин или пароль', 'msgClient' => 'Неверный логин или пароль'));
        }
        return array('error' => true, 'data' => array('errCode' => 'e403', 'msgDev' => 'Неверный логин или пароль', 'msgClient' => 'Неверный логин или пароль'));
    }

    
    /**
     * @OA\Post(
     *     path="/login/registration",
     *     summary="Регистрирует пользователя",
     *     tags={"login"},
     *     description="Метод для регистарции",
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     description="Email",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     description="Пароль, не менее 5 символов",
     *                     type="string"
     *                 ),
     *                @OA\Property(
     *                     property="login",
     *                     description="Login",
     *                     type="string",
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),

     * )
     */
    public function actionRegistration() {

        $request = Yii::$app->request;
        if ($request->isOptions) {
            return;
        }
        $params = \Yii::$app->request->post();

        if (!array_key_exists('email', $params) || empty($params['email'])) {
            return  JsonOutputHelper::getError('Не указан email');         
        }

        if (!array_key_exists('password', $params) || empty($params['password'])) {
            return  JsonOutputHelper::getError('Не указан password');         
        }

      

        if (!array_key_exists('login', $params) || empty($params['login'])) {
            return  JsonOutputHelper::getError('Не указано имя');         
        }

      

        if (strlen($params['password']) < 5)
            return JsonOutputHelper::getError('Пароль не может быть меньше 5 символов');     



        $u = \app\models\User::find()->where([
                    'email' => $params['email']
                ])->one();

        if ($u)
            return JsonOutputHelper::getError('Пользователь с таким email уже существует'); 
        
        $u = \app\models\User::find()->where([
                    'login' => trim($params['login'])
                ])->one();

        if ($u)
            return JsonOutputHelper::getError('Пользователь с таким login  уже существует'); 

        $user = new \app\models\User;        
        $user->email = $params['email'];
        $user->password = \Yii::$app->getSecurity()->generatePasswordHash($params['password']);
         $user->newPassword ='';
        $user->accessToken = $user->password;
        $user->login = trim($params['login']);       
        $user->role_id = \app\models\UserRole::CLIENT;
        $user->active = 1;
        if (!$user->validate()) {
            \Yii::trace(json_encode($user->getErrors()), __METHOD__);
              return JsonOutputHelper::getError(json_encode($user->getErrors()));            
        }

        $user->save();
          return JsonOutputHelper::getResult(array('user_id' => $user->id));
        }
  

    /**
     * @OA\Post(
     *     path="/login/logout",
     *     summary="Заменяет существующий токен доступа на случайный",
     *     tags={"login"},
     *     security={{"bearerAuth":{}}},
     *     description="Метод для отчистки авторизации",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Необходимо отправить авторизационный токен"
     *     ),


     * )
     */
    public function actionLogout() {

        $me = \Yii::$app->user->identity;
        $key = Yii::$app->getSecurity()->generateRandomString();
        $me->accessToken = $key;
        $me->save();


        return array('error' => false, 'data' => array());
    }

    
    /**
     * @OA\Post(
     *     path="/login/restore",
     *     summary="Отправляет на почту письмо восстановления пароля",
     *     tags={"login"},
     *     description="Метод для восстановления пароля",
     *     @OA\RequestBody(
     *         description="Input data format",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     description="Email",
     *                     type="string",
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),

     * )
     */
    public function actionRestore() {
        $email = $params = \Yii::$app->request->post()['email'];
        
        $u = \app\models\User::find()
                ->where(['email' => $email])
                ->andWhere(['active' => 1])
                ->one();
        if ($u) {
            $newPass = \Yii::$app->getSecurity()->generateRandomString(12);
            $u->newPassword = \Yii::$app->getSecurity()->generatePasswordHash($newPass);
            $u->save();
            $maildata = array('email'=> $email  , 'pass' => $newPass, 'name' => $u->login,'link' => \Yii::$app->params['serviceUrl'].'/login/changepass?token='.$u->newPassword);
            \Yii::$app->mailer->compose('passwordRestore', $maildata)
                    ->setFrom(\Yii::$app->params['adminEmail'])
                    ->setTo($u->email)
                    ->setSubject('Восстановление пароля')
                    ->send();
            return 'Пароль успешно изменен';
            return array('error' => false, 'data' => array());;
        }
        return array('error' => true, 'data' => array('errCode' => 'e404', 'msgDev' => 'Пользователь с таким именем не найден или неактивирован', 'msgClient' => 'Пользователь с таким именем не найден или неактивирован'));
      
    }
    
    
    /**
     * @OA\Get(
     *     path="/login/changepass",
     *     summary="Меняет пароль на ранее запрошенный через восстановление",
     *     tags={"login"},
     *     description="Метод для смены пароля на восстановленный",
     *     @OA\Parameter(
     *         name="token",
     *         in="query",            
     *         required=true,       
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation"
     *     ),

     * )
     */
    public function actionChangepass() {
        $params = \Yii::$app->request->get();
        $token =  $params['token'];
        if (empty($token)){
              return array('error' => true, 'data' => array('errCode' => 'e404', 'msgDev' => 'Токен не заполнен', 'msgClient' => 'Токен не заполнен'));
        }
        $u = \app\models\User::find()
                ->where(['newPassword' => $token])
                ->andWhere(['active' => 1])
                ->one();
        if ($u) {
            $newToken = \Yii::$app->getSecurity()->generateRandomString();
            $u->password  = $u->newPassword;
            $u->newPassword  = '';
            $u->accessToken = $newToken;
            $u->save();            
            return array('error' => false, 'data' => array());
        }
        return array('error' => true, 'data' => array('errCode' => 'e404', 'msgDev' => 'Пользователь с таким именем не найден или неактивирован', 'msgClient' => 'Пользователь с таким именем не найден или неактивирован'));
      
    }

}
