<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string $timestamp
 * @property int $useAdminGallery
 * @property string $updated_at
 * @property int $sale
 * @property int $hit
 * @property string $sid
 * @property int $supplier_id
 * @property int $brand_id
 * @property string $title
 * @property string $import_title
 * @property string $sku
 * @property string $code1c
 * @property string $description
 * @property string $certificate
 * @property string $barcode
 * @property string $unit
 * @property string $country
 * @property string $weight
 * @property string $depth
 * @property string $width
 * @property string $height
 * @property int $pack
 * @property int $min_order
 * @property string $price
 * @property string $price_add
 * @property int $quantity
 * @property int $fix
 * @property string $supplier_price
 * @property int $pre_deleted
 * @property int $deleted
 *
 * @property Brand $brand
 * @property Supplier $supplier
 * @property ProductAttach[] $productAttaches
 * @property ProductCategory[] $productCategories
 * @property Category[] $categories
 * @property ProductImage[] $productImages
 * @property Image[] $images
 * @property TradeMarkup[] $tradeMarkups
 */
class Product extends \yii\db\ActiveRecord {

    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['timestamp', 'updated_at'], 'safe'],
            [['useAdminGallery', 'sale', 'hit', 'supplier_id', 'brand_id', 'pack', 'min_order', 'quantity', 'fix', 'pre_deleted', 'deleted'], 'integer'],
            [['sid', 'supplier_id', 'title'], 'required'],
            [['description', 'certificate'], 'string'],
            [['weight', 'depth', 'width', 'height', 'price', 'price_add', 'supplier_price'], 'number'],
            [['sid'], 'string', 'max' => 50],
            [['title', 'import_title'], 'string', 'max' => 400],
            [['sku'], 'string', 'max' => 255],
            [['code1c', 'country'], 'string', 'max' => 255],
            [['barcode', 'unit'], 'string', 'max' => 20],
            [['sid', 'supplier_id'], 'unique', 'targetAttribute' => ['sid', 'supplier_id']],
            [['brand_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brand::className(), 'targetAttribute' => ['brand_id' => 'id']],
            [['supplier_id'], 'exist', 'skipOnError' => true, 'targetClass' => Supplier::className(), 'targetAttribute' => ['supplier_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'timestamp' => 'Timestamp',
            'useAdminGallery' => 'Use Admin Gallery',
            'updated_at' => 'Updated At',
            'sale' => 'Sale',
            'hit' => 'Hit',
            'sid' => 'Sid',
            'supplier_id' => 'Supplier ID',
            'brand_id' => 'Brand ID',
            'title' => 'Title',
            'import_title' => 'Import Title',
            'sku' => 'Sku',
            'code1c' => 'Code1c',
            'description' => 'Description',
            'certificate' => 'Certificate',
            'barcode' => 'Barcode',
            'unit' => 'Unit',
            'country' => 'Country',
            'weight' => 'Weight',
            'depth' => 'Depth',
            'width' => 'Width',
            'height' => 'Height',
            'pack' => 'Pack',
            'min_order' => 'Min Order',
            'price' => 'Price',
            'price_add' => 'Price Add',
            'quantity' => 'Quantity',
            'fix' => 'Fix',
            'supplier_price' => 'Supplier Price',
            'pre_deleted' => 'Pre Deleted',
            'deleted' => 'Deleted',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand() {
        return $this->hasOne(Brand::className(), ['id' => 'brand_id']);
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
    public function getProductAttaches() {
        return $this->hasMany(ProductAttach::className(), ['attached_product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductCategories() {
        return $this->hasMany(ProductCategory::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories() {
        return $this->hasMany(Category::className(), ['id' => 'category_id'])->viaTable('product_category', ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductImages() {
        return $this->hasMany(ProductImage::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages() {
        return $this->hasMany(Image::className(), ['id' => 'image_id'])->viaTable('product_image', ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTradeMarkups() {
        return $this->hasMany(TradeMarkup::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery 
     */
    public function getTradeMarkup($catalog_id) {
        return $this->hasOne(TradeMarkup::className(), ['product_id' => 'id'])->where(['catalog_id' => $catalog_id])->one();
    }

    public function beforeSave($insert) {
        if (!parent::beforeSave($insert)) {
            return false;
        }

       if (!$insert){
          if((floatval($this->price) != floatval($this->getOldAttribute('price'))) ||  
             ($this->quantity != $this->getOldAttribute('quantity'))){
              
              echo $this->id.'!!';
              echo $this->price.'!!';
              echo $this->quantity.'!!';
              echo $this->getOldAttribute('price').'!!';
              echo $this->getOldAttribute('quantity').'!!';      
              $this->timestamp = date('Y-m-d H:i:s');
           } 
       }
        return true;
    }

}
