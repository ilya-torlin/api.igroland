<?php

namespace app\components\report\reportPartEngine;

use app\components\report\reportPartEnginePositionReportPartEngine;

// класс для генерации таблиц показателей позиций для текущей поисковой службы
class PositionDynamicsReportPartEngine extends PositionReportPartEngine implements \app\components\report\ReportPartEngineInterface
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
         \Yii::trace($data['date_start']);
         \Yii::trace($data['date_end']);
        // получаем промежуток дат для вывода данных
        $last_date = \app\models\Position::find()->where(['project_id' => $id])
                                                            ->andWhere(['<', 'position.date', $date_start])
                                                            ->orderBy(['date' => SORT_DESC])
                                                            ->limit(1)
                                                            ->all();
        if(isset($last_date[0]->date)){
             $date_start = $last_date[0]->date;
        }else{
             $last_date = \app\models\Position::find()->where(['project_id' => $id])
                                                                 ->orderBy(['date' => SORT_ASC])
                                                                 ->limit(1)
                                                                 ->all();
               $date_start = $last_date[0]->date;
        }

        \Yii::trace($last_date);
        \Yii::trace($date_start);

        $start_date = \app\models\Position::find()->where(['project_id' => $id])
                                                            ->andWhere(['<=', 'position.date', $date_end])
                                                            ->orderBy(['date' => SORT_DESC])
                                                            ->limit(1)
                                                            ->all();
        \Yii::trace($start_date);
        if(isset($start_date[0]->date))
               $date_end = $start_date[0]->date;
        //\Yii::trace($date_end);
        /*$params['dateArray'] = \app\models\Position::find()->where(['project_id' => $id])
                                                            ->andWhere(['<=', 'position.date', $date_end])
                                                            ->andWhere(['>=', 'position.date', $date_start])
                                                            ->orderBy(['date' => SORT_ASC])
                                                            //->indexBy('id')
                                                            ->all();*/
      $params['dateArray'] = [$date_start,$date_end];

      // получаем данныйи и выводим в формате массива - array[id позиции][id запроса]
        $params['start_data'] = $this->getPostitionData($id,$date_start,$date_start,$sengine_id);
        //\Yii::trace( $params['start_data']);
        $params['end_data'] = $this->getPostitionData($id,$date_end,$date_end,$sengine_id);
        //\Yii::trace($params['end_data']);
        $dinamics = array();
        foreach ($params['end_data'] as $key => $value) {
             $position1 = $params['start_data'][$key][$last_date[0]->id];
            
        if (!array_key_exists($start_date[0]->id,$value)){
            $position2 = 100;            
        } else {
            $position2 = $value[$start_date[0]->id];
        }
             
             
              if (!$position1) $position1 = 100;
              if (!$position2) $position2 = 100;
             
             $dinamics[$key] = $position1 - $position2;
        }
        $params['dinamics'] = $dinamics;
        // получаем настройки текущей части отчета reportPartReport
        //$params['colors'] = $this->getReportPartSettings($report_part_id);  // в json формате
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/dynamicspositions.php', $params);
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
