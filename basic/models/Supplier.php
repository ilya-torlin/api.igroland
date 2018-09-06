<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "supplier".
 *
 * @property int $id
 * @property string $timestamp
 * @property string $title
 * @property string $code
 * @property string $inn
 * @property string $link
 * @property string $price_add
 * @property string $email
 * @property string $importClass
 * @property string $importLastFinish
 * @property int $importIsRun
 * @property int $importIsActive
 * @property int $importDelayTime
 *
 * @property Catalog[] $catalogs
 * @property Category[] $categories
 * @property Product[] $products
 */
class Supplier extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'supplier';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['timestamp', 'importLastFinish'], 'safe'],
            [['title', 'code', 'inn'], 'required'],
            [['price_add'], 'number'],
            [['importIsRun', 'importIsActive', 'importDelayTime'], 'integer'],
            [['title', 'link'], 'string', 'max' => 256],
            [['code'], 'string', 'max' => 15],
            [['inn'], 'string', 'max' => 12],
            [['email', 'importClass'], 'string', 'max' => 255],
            [['inn'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'timestamp' => 'Timestamp',
            'title' => 'Title',
            'code' => 'Code',
            'inn' => 'Inn',
            'link' => 'Link',
            'price_add' => 'Price Add',
            'email' => 'Email',
            'importClass' => 'Import Class',
            'importLastFinish' => 'Import Last Finish',
            'importIsRun' => 'Import Is Run',
            'importIsActive' => 'Import Is Active',
            'importDelayTime' => 'Import Delay Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCatalogs()
    {
        return $this->hasMany(Catalog::className(), ['supplier_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::className(), ['supplier_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['supplier_id' => 'id']);
    }
}
