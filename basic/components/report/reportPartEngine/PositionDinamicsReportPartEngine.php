<?php

namespace app\components\report\reportPartEngine;

use app\components\report\reportPartEnginePositionReportPartEngine;

// класс для генерации таблиц показателей позиций для текущей поисковой службы
class PositionDinamicsReportPartEngine extends PositionReportPartEngine implements \app\components\report\ReportPartEngineInterface
{
    public function getAlert(){
      return false;
    }
    
    public function render($data){
         // заполняем часть отчета - таблицы по позициям
         // идентификатор службы поиска
        $sengine_id = $data['sengine_id'];
        // идентификатор проекта
        $id = $data['project_id'];
        // идентификатор части отчета reportPartReport
        $report_part_id = $data['report_part_id'];
        // дата начала текущего отчета
        $date_start =  $data['date_start'];
        // дата конца текущего отчета
        $date_end =  $data['date_end'];
        // задаем порядок
        $params['order'] = $data['order'];
        // получаем текущую поисковую службу
        $params['engine'] = \app\models\Sengine::find()->where(['id' => $sengine_id])->one();
        // получаем список запросов для текущего проекта
        $params['queryArray'] = \app\models\Query::find()->where(['project_id' => $id])->indexBy('id')->all();
        // получаем промежуток дат для вывода данных
        $last_date = \app\models\Position::find()->where(['project_id' => $id])
                                                            ->andWhere(['<', 'position.date', $date_start])
                                                            ->orderBy(['date' => SORT_DESC])
                                                            ->limit(1)
                                                            ->all();
        if(isset($last_date[0]->date))
               $date_start = $last_date[0]->date;
        \Yii::trace($date_start);
        $params['dateArray'] = \app\models\Position::find()->where(['project_id' => $id])
                                                            ->andWhere(['<=', 'position.date', $date_end])
                                                            ->andWhere(['>=', 'position.date', $date_start])
                                                            ->orderBy(['date' => SORT_ASC])
                                                            //->indexBy('id')
                                                            ->all();


      // получаем данныйи и выводим в формате массива - array[id позиции][id запроса]
        $params['data'] = $this->getPostitionData($id,$date_start,$date_end,$sengine_id);
        // получаем настройки текущей части отчета reportPartReport
        //$params['colors'] = $this->getReportPartSettings($report_part_id);  // в json формате
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/positions.php', $params);
        return $html;
    }

    public function getPostitionData($id, $start, $end, $engine){
         // полученные данные выводим в формате массива - array[id позиции][id запроса]
         $currentPositions = $this->getPostition($id,$start,$end,$engine);
         $positionsArray=array();
          foreach($currentPositions as $item){
               $positionsArray[$item['idquery']][$item['idposition']] = $item['position_value'];
          }
          return $positionsArray;
     }

}
