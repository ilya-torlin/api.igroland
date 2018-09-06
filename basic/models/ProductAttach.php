<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_attach".
 *
 * @property int $id
 * @property int $category_id
 * @property int $attached_product_id
 *
 * @property Category $category
 * @property Product $attachedProduct
 */
class ProductAttach extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_attach';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'attached_product_id'], 'required'],
            [['category_id', 'attached_product_id'], 'integer'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['attached_product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['attached_product_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'attached_product_id' => 'Attached Product ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachedProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'attached_product_id']);
    }
}
