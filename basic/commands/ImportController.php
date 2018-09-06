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
                $importer = \app\components\import\ImporterFactory::create($supplier->importClass);
                $result = $importer->engine($supplier);
                echo '<p>';
                var_dump($result);
                echo '</p>';
               // $supplier->importIsRun = 0;
               // $supplier->save();
            }
        }
        
    }
}
