<?php


namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property integer $role_id
 * @property string $login
 * @property string $email
 * @property string $password
 * @property string $newPassword
 * @property string $accessToken
 * @property string $authKey
 * @property integer $active
 * @property string $regDate
 * @property string $lastAction

 * @property UserRole $role 
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
            [[ 'role_id', 'active'], 'integer'],
            [['regDate', 'lastAction'], 'safe'],
            [['login',  'email', 'password', 'accessToken', 'authKey', 'newPassword'], 'string', 'max' => 255],
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
            'newPassword' => 'New password',
            'accessToken' => 'Access Token',
            'authKey' => 'Auth Key',
            'active' => 'Active',            
            'regDate' => 'Reg Date',
            'lastAction' => 'Last Action',  
        ];
    }

    public function fields() {
        $fields = parent::fields();
        // удаляем поля, содержащие конфиденциальную информацию
        unset($fields['password'], $fields['authKey'], $fields['accessToken']);
        $fields['role'] = function ($model) {
            return $model->role->toArray();
        };

        return $fields;
    }

    

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole() {
        return $this->hasOne(UserRole::className(), ['id' => 'role_id']);
    }

   

    public function getAuthKey(): string {
        //  return $this->authKey;
    }

    public function getId() {
        return $this->id;
    }

    public function validateAuthKey($authKey): bool {
        //  return $this->getAuthKey() === $authKey;
    }

    public static function findIdentity($id): \yii\web\IdentityInterface {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null): \yii\web\IdentityInterface {
        //return static::findOne(1);
        return static::findOne(['accessToken' => $token]);
    }

}
