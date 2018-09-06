<?php

namespace app\components\report\reportPartEngine;

use app\components\report\reportPartEnginePositionReportPartEngine;

//// класс для генерации сводного отчета (графика Pie), который наследуется от класса получения позиций
class PositionSummaryReportPartEngine extends PositionReportPartEngine implements \app\components\report\ReportPartEngineInterface
{
     public function getAlert(){
      return false;
    }
    public function render($data){
         // заполняем часть отчета - таблицы по позициям
         // id проекта, для которого генерируем отчет
        $id = $data['project_id'];
        // id текущего reportPertReport
        $report_part_id = $data['report_part_id'];
        // начальная дата месяца
        $date_start =  $data['date_start'];
        // конечная дата месяца
        $date_end =  $data['date_end'];
        // задаем порядок
        $params['order'] = $data['order'];
        // Получаем массив данных необходимых для построения графика (Pie) суммы позиций топов
        $params['data'] = $this->getPostitionData($id,$date_start,$date_end,$report_part_id);
        // получаем настройки у текущего reportPertReport
        $settings = $this->getReportPartSettings($report_part_id);  // в json формате
        // из настроек достаем название части отчета, поисковую систему, настройки позиций
        foreach ($settings as $setting) {
            // название части отчета
            if ($setting['type'] == 'name') {
                 $params['name'] = $setting['value'];
            // поисковую систему
            } else if ($setting['type'] == 'select'){
                 $params['engine'] = $setting['value']['name_se'] ." ". $setting['value']['name_region'];
            // настройки позиций
            } else if ($setting['type'] == 'text') {
                 $color[$setting['name']] = $setting['value'];
            }
        }
        // готовим данные для рендера шаблона charts
        $params['report_part_id'] = $report_part_id;
        $params['color'] = $color;
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/charts.php', $params);
        return $html;
    }

    public function getPostitionData($project_id, $start, $end,$report_part_id){
         // получаем позиции поисковых запросов у проекта $id у системы поиска $engine за даты со $start по $end
          $engines = \app\models\Sengine::find()->where(['project_id' => $project_id])->all();
          // получаем настройки текущей части отчета
          $params = $this->getReportPartSettings($report_part_id);  // в json формате
          // формируем настройки позиций
          foreach ($params as $param) {
               if ($param['type'] == 'text') {
                    $limit[$param['name']] = $param['value'];
               }
          }
          // проходимся по всем поисковым службам (Яндекс, Гугл и т.д.) и по выборке данных и плюсуем к той позиции,в которую попадает значение позиции запроса на данном этапе
          foreach ($engines as $key => $eng) {
               // получаем позиции для текущей поисковой службы
               $positions =  $this->getPostition($project_id,$start,$end,$eng->id);
               //\Yii::trace(print_r($positions,true));
               // обнуляем массив показателей для текущей службы
               $count = array(0,0,0,0);
               foreach ($positions as $item) {
                    if($item['position_value'] == 0){
                         $count[3]++;
                    }
                    else if($item['position_value'] <= $limit['green']){
                         $count[0]++;
                    }
                    else if ($item['position_value'] <= $limit['yel']){
                         $count[1]++;
                    }
                    else if ($item['position_value'] <=  $limit['orange']){
                         $count[2]++;
                    }
                    else $count[3]++;
               }
               // в массив с key = "название службы" записываем массив значений.
               $currentPositions[$eng->name_se] = $count;
               //\Yii::trace(count($positions,true));
          }
          // возвращаем массив массивов
          return $currentPositions;
     }

}
