<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "export".
 *
 * @property int $id
 * @property int $user_id
 * @property int $catalog_id
 * @property string $link
 * @property string $optPriceAdd
 * @property string $roznPriceAdd
 *
 * @property Catalog $catalog
 * @property User $user
 */
class Export extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'export';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'catalog_id', 'link'], 'required'],
            [['user_id', 'catalog_id'], 'integer'],
            [['optPriceAdd', 'roznPriceAdd'], 'number'],
            [['link'], 'string', 'max' => 50],
            [['link'], 'unique'],
            [['catalog_id'], 'exist', 'skipOnError' => true, 'targetClass' => Catalog::className(), 'targetAttribute' => ['catalog_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'catalog_id' => 'Catalog ID',
            'link' => 'Link',
            'optPriceAdd' => 'Opt Price Add',
            'roznPriceAdd' => 'Rozn Price Add',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCatalog()
    {
        return $this->hasOne(Catalog::className(), ['id' => 'catalog_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
