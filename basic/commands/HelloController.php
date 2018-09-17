<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world')
    {
         $importer = \app\components\import\ImporterFactory::create('SimaImporter');
         //echo 'importer';
         $suppliers= \app\models\Supplier::find()->where(['id' => 7])->one();

//         $model = \app\models\Category::find()->where(['title' => 'БУКВА-ЛЕНД', 'catalog_id' => 7])->one();
//         print_r($model);
         /* {"id":11748,"sid":"Р00010974","name":"БУКВА-ЛЕНД","description":"","slug":"bukva-lend","photo":"Bukva-lend.gif","is_exclusive":1} */
         $importer->engine($suppliers);
    }
}
