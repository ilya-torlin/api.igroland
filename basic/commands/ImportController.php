<?php
namespace app\commands;

use yii\console\Controller;

class ImportController extends Controller
{
    public function actionIndex($id = false)
    {
        if (!$id){
            $suppliers= \app\models\Supplier::find()->where(['importIsRun' => 0,'importIsActive' => 1])->all();
        } else {
           $suppliers= \app\models\Supplier::find()->where(['id' => $id])->all(); 
        }
        
       
        foreach ($suppliers as $supplier){
            $minStartDate = date('Y-m-d h:i:s',(strtotime($supplier->importLastFinish) + $supplier->importDelayTime));            
            if (date('Y-m-d h:i:s') > $minStartDate){
                //$supplier->importIsRun = 1;
                //$supplier->save();
                \app\models\Product::updateAll(['pre_deleted' => 1], ['=', 'supplier_id', $supplier->id]);
                \app\models\Category::updateAll(['pre_deleted' => 1], ['=', 'supplier_id', $supplier->id]);
                $importer = \app\components\import\ImporterFactory::create($supplier->importClass);
                $result = $importer->engine($supplier);
                echo '<p>';
                var_dump($result);
                echo '</p>';
                \app\models\Product::updateAll(['deleted' => 1], 'supplier_id = '.$supplier->id.' and pre_deleted = 1');
                \app\models\Category::updateAll(['deleted' => 1], 'supplier_id = '.$supplier->id.' and pre_deleted = 1');
               // $supplier->importIsRun = 0;
               // $supplier->save();
            }
        }
        
    }
}
