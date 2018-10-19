<?php
namespace app\commands;

use yii\console\Controller;

class ImportController extends Controller
{
    public function actionIndex($id = false)
    {
        date_default_timezone_set("Asia/Yekaterinburg"); 
        if (!$id){
            $suppliers= \app\models\Supplier::find()->where(['importIsRun' => 0,'importIsActive' => 1])->orderBy(['sort' => SORT_ASC])->all();
        } else {
           $suppliers= \app\models\Supplier::find()->where(['id' => $id])->all(); 
        }
        
        
        foreach ($suppliers as $supplier){
            $minStartDate = date('Y-m-d H:i:s',(strtotime($supplier->importLastFinish) + $supplier->importDelayTime));            
            if (date('Y-m-d H:i:s') > $minStartDate || $id){
                $supplier->importIsRun = 1;
                $supplier->save();
                \app\models\Product::updateAll(['pre_deleted' => 1], ['=', 'supplier_id', $supplier->id]);
                \app\models\Category::updateAll(['pre_deleted' => 1], ['=', 'supplier_id', $supplier->id]);
                $importer = \app\components\import\ImporterFactory::create($supplier->importClass);
                $result = $importer->engine($supplier);
                echo '<p>';
                var_dump($result);
                echo '</p>';
                if (is_array($result) && $result['success']){
                      \app\models\Product::updateAll(['quantity' => 0,'deleted' => 1,'timestamp' => date('Y-m-d H:i:s')], 'supplier_id = '.$supplier->id.' and pre_deleted = 1');
                      \app\models\Category::updateAll(['deleted' => 1], 'supplier_id = '.$supplier->id.' and pre_deleted = 1');
                } else {
                     \app\models\Product::updateAll(['pre_deleted' => 0], ['=', 'supplier_id', $supplier->id]);
                    \app\models\Category::updateAll(['pre_deleted' => 0], ['=', 'supplier_id', $supplier->id]);
                }
              
                $supplier->importIsRun = 0;
                $supplier->importLastFinish = date('Y-m-d H:i:s');
                $supplier->save();
                return;
            }
        }
        
    }
}
