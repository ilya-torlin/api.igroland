<?php


namespace app\models;

use Yii;

//**
/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property int $role_id
 * @property string $login
 * @property string $email
 * @property string $password
 * @property string $accessToken
 * @property string $newPassword
 * @property string $authKey
 * @property int $isActive
 * @property int $image_id
 * @property string $surname
 * @property string $name
 * @property string $patronymic
 * @property string $site
 * @property string $phone
 * @property string $photo
 * @property string $regDate
 * @property string $lastAction
 *
 * @property Catalog[] $catalogs
 * @property TradeMarkup[] $tradeMarkups
 * @property UserRole $role
 * @property UserCatalog[] $userCatalogs
 * @property Catalog[] $catalogs0
 */

/**
 @OAS\Schema(
 *     title="User model",
 *     description="User model",
 *     type="object"
 * )
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
         return [
              [['role_id', 'password', 'accessToken'], 'required'],
              [['role_id', 'isActive', 'image_id'], 'integer'],
              [['regDate', 'lastAction'], 'safe'],
              [['login', 'email', 'password', 'accessToken', 'newPassword', 'authKey', 'photo'], 'string', 'max' => 255],
              [['surname', 'name', 'patronymic', 'site'], 'string', 'max' => 100],
              [['phone'], 'string', 'max' => 12],
              [['login'], 'unique'],
              [['email'], 'unique'],
              [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => UserRole::className(), 'targetAttribute' => ['role_id' => 'id']],
         ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
         return [
              'id' => 'ID',
              'role_id' => 'Role ID',
              'login' => 'Login',
              'email' => 'Email',
              'password' => 'Password',
              'accessToken' => 'Access Token',
              'newPassword' => 'New Password',
              'authKey' => 'Auth Key',
              'isActive' => 'Is Active',
              'surname' => 'Surname',
              'name' => 'Name',
              'patronymic' => 'Patronymic',
              'site' => 'Site',
              'phone' => 'Phone',
              'photo' => 'Photo',
              'regDate' => 'Reg Date',
              'lastAction' => 'Last Action',
              'image_id' => 'Image Id'
         ];
    }

    public function fields() {
        $fields = parent::fields();
        // удаляем поля, содержащие конфиденциальную информацию
        unset($fields['password'], $fields['authKey'], $fields['accessToken']);
        $fields['role'] = function ($model) {
            return $model->role->toArray();
        };
         $fields['image'] = function ($model) {
              if (isset($model->image))
               return $model->image->toArray();
         };

        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole() {
        return $this->hasOne(UserRole::className(), ['id' => 'role_id']);
    }

     /**
      * @return \yii\db\ActiveQuery
      */
     public function getImage()
     {
          return $this->hasOne(Image::className(), ['id' => 'image_id']);
     }




     public function getAuthKey() {
        //  return $this->authKey;
    }

    public function getId() {
        return $this->id;
    }

    public function validateAuthKey($authKey) {
        //  return $this->getAuthKey() === $authKey;
    }

    public static function findIdentity($id) {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null) {
        //return static::findOne(1);
        return static::findOne(['accessToken' => $token]);
    }

}
