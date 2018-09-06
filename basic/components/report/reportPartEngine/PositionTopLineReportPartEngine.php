<?php

namespace app\components\report\reportPartEngine;

use app\components\report\reportPartEnginePositionReportPartEngine;

// класс для генерации графика показателей позиций в ТОП текущей поисковой службы
class PositionTopLineReportPartEngine extends PositionReportPartEngine implements \app\components\report\ReportPartEngineInterface
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
        //$params['engine'] = \app\models\Sengine::find()->where(['id' => $sengine_id])->one();
        // получаем список запросов для текущего проекта
        $params['queryArray'] = \app\models\Query::find()->where(['project_id' => $id])->indexBy('id')->all();
        // получаем промежуток дат для вывода данных
        $last_date = \app\models\Position::find()->where(['project_id' => $id])
                                                            ->andWhere(['<=', 'position.date', $date_start])
                                                            ->orderBy(['date' => SORT_DESC])
                                                            ->limit(1)
                                                            ->all();
        if(isset($last_date[0]->date))
               $date_start = $last_date[0]->date;
        $dates = \app\models\Position::find()->select(['id','date'])->where(['project_id' => $id])
                                                            ->andWhere(['<=', 'position.date', $date_end])
                                                            ->andWhere(['>=', 'position.date', $date_start])
                                                             ->orderBy(['position.date' => SORT_ASC])
                                                            ->indexBy('id')->all();
        $params['dateArray'] = $dates;
        // получаем настройки текущей части отчета reportPartReport
        $settings = $this->getReportPartSettings($report_part_id);  // в json формате
        foreach ($settings as $setting) {
            // название части отчета
            if ($setting['type'] == 'name') {
                 $params['name'] = $setting['value'];
            // поисковую систему
            } else if ($setting['type'] == 'select'){
                 $params['engine'] = $setting['value']['name_se'] ." ". $setting['value']['name_region'];
            // настройки позиций
            } else if ($setting['type'] == 'text') {
                 $colors[$setting['name']] = $setting['value'];
            }
        }
        $params['colors'] = $colors;
        $segines = \app\models\Sengine::find()->where(['project_id' => $id])->asArray()->all();
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/LinesAPhead.php', $params);
        $scrpt = '';
        $script =   '<script type="text/javascript">function initLinesAP(){ var dataObj;';
        $canvas = '';
        foreach ($segines as $eng) {
             $params['order']++;
             // получаем данныйи и выводим в формате массива - array[id даты][id цвета]
             $params['data'] = $this->getPostitionData($id,$date_start,$date_end,$eng['id'],$dates,$colors);
             //\Yii::trace(print_r($params['colors'],true));
             //\Yii::trace(print_r($params['data'],true));
             $scrpt .= \Yii::$app->view->renderFile('@app/views/report/worksdone/LinesAP.php', $params);
             $tmp = $params["name"].' - '.$eng["name_se"].' '.$eng["name_region"];
             $canvas .= '<div class="col-xs-12"><h2 class="h2">'.$tmp.'</h2></div><div class="col-xs-12"><canvas id="graphColeb'.$params["order"].'" class="graph"></canvas></div>';
        }
        $script = $script.$scrpt.'}</script>';
        $html .= $canvas.$script.\Yii::$app->view->renderFile('@app/views/report/worksdone/LinesAPfooter.php', $params);
        return $html;
    }

    public function getPostitionData($id, $start, $end, $engine, $dates, $limit){
         // полученные данные выводим в формате массива - array[id даты][id цвета]
         $currentPositions = $this->getPostition($id,$start,$end,$engine);
         $count = array();
         foreach ($dates as $key => $date) {
              $i=$j=$k=$l=0;
             foreach($currentPositions as $item){
                  //\Yii::trace(print_r($item['position_date'],true));
                  // \Yii::trace(print_r($date,true));
                  if($item['position_date'] == $date['date']){
                       if($item['position_value'] == 0){
                           $count[$key]['grey'] = ++$l;
                      }
                      else if($item['position_value'] <= $limit['green']){
                           //\Yii::trace(print_r($item,true));
                           $count[$key]['green'] = ++$i;
                      }
                      else if ($item['position_value'] <= $limit['yel']){
                           $count[$key]['yel'] = ++$j;
                      }
                      else if ($item['position_value'] <=  $limit['orange']){
                           $count[$key]['orange'] = ++$k;
                      }
                      else $count[$key]['red'] = ++$l;
                  }
             }
         }
          return $count;
     }

}
