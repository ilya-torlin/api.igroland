<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "catalog".
 *
 * @property int $id
 * @property string $name
 * @property string $timestamp
 * @property int $image_id
 * @property int $isOn
 * @property int $user_id
 * @property int $avlForAll
 * @property int $supplier_id
 * @property string $description
 *
 * @property Image $image
 * @property Supplier $supplier
 * @property User $user
 * @property Category[] $categories
 * @property UserCatalog[] $userCatalogs
 * @property User[] $users
 */
class Catalog extends \yii\db\ActiveRecord {

    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return 'catalog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['timestamp'], 'safe'],
            [['image_id', 'isOn', 'user_id', 'avlForAll', 'supplier_id'], 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['image_id'], 'exist', 'skipOnError' => true, 'targetClass' => Image::className(), 'targetAttribute' => ['image_id' => 'id']],
            [['supplier_id'], 'exist', 'skipOnError' => true, 'targetClass' => Supplier::className(), 'targetAttribute' => ['supplier_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'timestamp' => 'Timestamp',
            'image_id' => 'Image ID',
            'isOn' => 'Is On',
            'user_id' => 'User ID',
            'avlForAll' => 'Avl For All',
            'supplier_id' => 'Supplier ID',
            'description' => 'Description',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage() {
        return $this->hasOne(Image::className(), ['id' => 'image_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupplier() {
        return $this->hasOne(Supplier::className(), ['id' => 'supplier_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser() {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories() {
        return $this->hasMany(Category::className(), ['catalog_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserCatalogs() {
        return $this->hasMany(UserCatalog::className(), ['catalog_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers() {
        return $this->hasMany(User::className(), ['id' => 'user_id'])->viaTable('user_catalog', ['catalog_id' => 'id']);
    }

    public function setUpdate() {
        $this->timestamp = date('Y-m-d H:i:s', time() + 5 * 60 * 60);
    }
    public function setAndSaveUpdate() {
        $this->setUpdate();
        $this->save();
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
             $this->setUpdate();
            return true;
        }
        return false;
    }

}
