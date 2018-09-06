<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "category_attach".
 *
 * @property int $id
 * @property int $category_id
 * @property int $attached_category_id
 *
 * @property Category $category
 * @property Category $attachedCategory
 */
class CategoryAttach extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category_attach';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'attached_category_id'], 'required'],
            [['category_id', 'attached_category_id'], 'integer'],
            [['attached_category_id', 'category_id'], 'unique', 'targetAttribute' => ['attached_category_id', 'category_id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['attached_category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['attached_category_id' => 'id']],
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
            'attached_category_id' => 'Attached Category ID',
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
    public function getAttachedCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'attached_category_id']);
    }
}
