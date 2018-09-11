<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "category".
 *
 * @property int $id
 * @property string $timestap
 * @property int $parent_id
 * @property string $title
 * @property int $supplier_id
 * @property int $catalog_id
 * @property int $external_id
 * @property int $internal_id
 * @property int $pre_deleted
 * @property int $deleted
 *
 * @property Catalog $catalog
 * @property Category $parent
 * @property Category[] $categories
 * @property Supplier $supplier
 * @property CategoryAttach[] $categoryAttaches
 * @property CategoryAttach[] $categoryAttaches0
 * @property Category[] $attachedCategories
 * @property Category[] $categories0
 * @property ProductAttach[] $productAttaches
 * @property ProductCategory[] $productCategories
 * @property Product[] $products
 */
class Category extends \yii\db\ActiveRecord {

    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['timestap'], 'safe'],
            [['parent_id', 'supplier_id', 'catalog_id', 'external_id', 'internal_id', 'pre_deleted', 'deleted'], 'integer'],
            [['title'], 'required'],
            [['title'], 'string', 'max' => 128],
            [['supplier_id', 'external_id'], 'unique', 'targetAttribute' => ['supplier_id', 'external_id']],
            [['catalog_id'], 'exist', 'skipOnError' => true, 'targetClass' => Catalog::className(), 'targetAttribute' => ['catalog_id' => 'id']],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['parent_id' => 'id']],
            [['supplier_id'], 'exist', 'skipOnError' => true, 'targetClass' => Supplier::className(), 'targetAttribute' => ['supplier_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'timestap' => 'Timestap',
            'parent_id' => 'Parent ID',
            'title' => 'Title',
            'supplier_id' => 'Supplier ID',
            'catalog_id' => 'Catalog ID',
            'external_id' => 'External ID',
            'internal_id' => 'Internal ID',
            'pre_deleted' => 'Pre Deleted',
            'deleted' => 'Deleted',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCatalog() {
        return $this->hasOne(Catalog::className(), ['id' => 'catalog_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent() {
        return $this->hasOne(Category::className(), ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories() {
        return $this->hasMany(Category::className(), ['parent_id' => 'id']);
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
    public function getCategoryAttaches() {
        return $this->hasMany(CategoryAttach::className(), ['category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategoryAttaches0() {
        return $this->hasMany(CategoryAttach::className(), ['attached_category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachedCategories() {
        return $this->hasMany(Category::className(), ['id' => 'attached_category_id'])->viaTable('category_attach', ['category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories0() {
        return $this->hasMany(Category::className(), ['id' => 'category_id'])->viaTable('category_attach', ['attached_category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductAttaches() {
        return $this->hasMany(ProductAttach::className(), ['category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductCategories() {
        return $this->hasMany(ProductCategory::className(), ['category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts() {
        return $this->hasMany(Product::className(), ['id' => 'product_id'])->viaTable('product_category', ['category_id' => 'id']);
    }

    public function getInnerProducts() {
        $categoryIds = array($this->id);
        $depth = 0;
        $models = \app\models\Category::find();
        $models = $models->leftJoin('category_attach', 'category_attach.attached_category_id = category.id');
        $models = $models->where(['parent_id' => $this->id]);
        $models = $models->orWhere(['category_attach.category_id' => $this->id]);
        $newCategoryIds = $models->select('category.id')->asArray()->column();

        while (count($newCategoryIds) > 0 && $depth < 10) {
            $categoryIds = array_merge($categoryIds, $newCategoryIds);
            $models = \app\models\Category::find();
            $models = $models->leftJoin('category_attach', 'category_attach.attached_category_id = category.id');
            $models = $models->where(['IN', 'parent_id', $newCategoryIds]);
            $models = $models->orWhere(['IN', 'category_attach.category_id', $newCategoryIds]);
            $newCategoryIds = $models->select('category.id')->asArray()->column();
            $depth++;
        }


        //Выбираем товары вложенные в категории
        $models = \app\models\ProductCategory::find();
        $models = $models->andWhere(['IN', 'category_id', $categoryIds]);
        $modelIds = $models->select('product_id')->asArray()->column();


        //Выбираем товары attached в категории
        $models = \app\models\Product::find();
        $models = $models->innerJoin('product_attach', 'product.id = product_attach.attached_product_id');
        $models = $models->andWhere(['IN', 'product_attach.category_id', $categoryIds]);
        $modelIds = array_merge($modelIds, $models->select('product.id')->asArray()->column());
        //var_dump($modelIds);

        return \app\models\Product::find()->where(['IN', 'id', $modelIds])->andWhere(['deleted' => 0]);
    }

    public function getChildProducts() {
        $categoryIds = array($this->id);
        $depth = 0;
        $models = \app\models\Category::find();
        $models = $models->leftJoin('category_attach', 'category_attach.attached_category_id = category.id');
        $models = $models->where(['category_attach.category_id' => $this->id]);
        $newCategoryIds = $models->select('category.id')->asArray()->column();

        while (count($newCategoryIds) > 0 && $depth < 10) {
            $categoryIds = array_merge($categoryIds, $newCategoryIds);
            $models = \app\models\Category::find();
            $models = $models->leftJoin('category_attach', 'category_attach.attached_category_id = category.id');
            $models = $models->where(['IN', 'category_attach.category_id', $newCategoryIds]);
            $newCategoryIds = $models->select('category.id')->asArray()->column();
            $depth++;
        }


        //Выбираем товары вложенные в категории
        $models = \app\models\ProductCategory::find();
        $models = $models->andWhere(['IN', 'category_id', $categoryIds]);
        $modelIds = $models->select('product_id')->asArray()->column();


        //Выбираем товары attached в категории
        $models = \app\models\Product::find();
        $models = $models->innerJoin('product_attach', 'product.id = product_attach.attached_product_id');
        $models = $models->andWhere(['IN', 'product_attach.category_id', $categoryIds]);
        $modelIds = array_merge($modelIds, $models->select('product.id')->asArray()->column());
        //var_dump($modelIds);

        return \app\models\Product::find()->where(['IN', 'id', $modelIds])->andWhere(['deleted' => 0]);
    }
}
