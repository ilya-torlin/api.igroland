<?php

namespace app\components\report\reportPartEngine;

// класс для генерации данных с яндекс метрики
class YandexMetrikaReportPartEngine implements \app\components\report\ReportPartEngineInterface
{
     public function getAlert(){
      return false;
    }
    private function printSeconds($seconds){
         $seconds =  intval($seconds);
         $out='';
         if ($seconds>=60){
            $min =  (int)($seconds/60);
            $seconds = $seconds - $min*60;
            $out = $min.'м. ';
         }
         if ($seconds>0){
            $out = $out.$seconds.'c.';
         }
         return $out;
    }
    public function render($data){
         // заполняем часть отчета - таблицы по позициям

        // идентификатор проекта
        $id = $data['project_id'];
        //\Yii::trace($id);
        $cur_project = \app\models\Project::find()->where(['id' => $id])->one();

        // идентификатор части отчета reportPartReport
        $report_part_id = $data['report_part_id'];

        $settings = $this->getReportPartSettings($report_part_id);
        //\Yii::trace($settings[1]['value']);
        // название части отчета
        $params['name'] = $settings[0]['value'];
        // задаем начальную дату, если 0 - то с начала ведения проекта, если другое значение, то кол-во месяцев из настроек - данные всегд аза 3 месяца

     //    if ($settings[1]['value'] == 0){
     //    $date_start =  $cur_project['start_date'];
     //    }
     //    else {
     //    $date_start = date('Y-m-d',strtotime("now -".$settings[1]['value']." month"));
     //    }
        $date_start = date('Y-m-d',strtotime($data['date_start']." -2 month"));


        // дата конца текущего отчета
        $date_end =  $data['date_end'];

        $params['ya_metrika_id'] = $cur_project->yandex_metrika_id;

        $ya_account = \app\models\YandexAccount::find()->where(['id' => $cur_project->yandex_account_id])->one();
        // дата конца текущего отчета
        $params['ya_token'] =  $ya_account->token;
        // задаем порядок
        $params['order'] = $data['order'];

        // получаем значения по API метрики
        $start_array = \app\components\MetrikaHelper::getCommonTable($params['ya_metrika_id'],$params['ya_token'],$date_start,$date_end);
        $monthes = $this->getPeriod($date_start,$date_end);
        // заголовок для таблиц из метрики
        $header_array = ['','Визиты','Посетители','Отказы','Глубина просмотра','Время на сайте'];

        // заголвок для общей таблицы показателей
        $header_total = array();
        $header_total[] = 'Показатели';
        foreach ($monthes as $month) {
             $header_total[] = $month;
        }
        $week_array = $finish_array = array();
        \Yii::trace('$start_array[]');
        //\Yii::trace(print_r($start_array['data'],true));
        // формируем массив для графика и таблиц отдельных показателей
        $week_array = \app\components\MetrikaHelper::getCommonTableByWeek($params['ya_metrika_id'],$params['ya_token'],$date_start,$date_end);
        // группирем данные для графиков (по неделям)
        foreach ($week_array['data'] as $ya_report) {
            for ($i=0; $i < count($week_array['intervals']); $i++) {
               // формируем массив для графика и таблиц отдельных показателей
               $graph_array[$ya_report['name']][$i][0] = $week_array['intervals'][$i][1];
               $graph_array[$ya_report['name']][$i][1] = $ya_report['visits'][$i];
               $graph_array[$ya_report['name']][$i][2] = $ya_report['users'][$i];
               $graph_array[$ya_report['name']][$i][3] = round(floatval($ya_report['bounceRate'][$i]),2);
               $graph_array[$ya_report['name']][$i][4] = round(floatval($ya_report['pageDepth'][$i]),2);
               $graph_array[$ya_report['name']][$i][5] = $this->printSeconds($ya_report['avgVisitDurationSeconds'][$i]);
            }
        }
        // группируем данные для таблиц по месяцам
        foreach ($start_array['data'] as $ya_report) {
            for ($i=0; $i < count($monthes); $i++) {
               // формируем массив для графика и таблиц отдельных показателей
               $finish_array[$ya_report['name']][$i][0] = $monthes[$i];
               $finish_array[$ya_report['name']][$i][1] = $ya_report['visits'][$i];
               $finish_array[$ya_report['name']][$i][2] = $ya_report['users'][$i];
               $finish_array[$ya_report['name']][$i][3] = round(floatval($ya_report['bounceRate'][$i]),2);
               $finish_array[$ya_report['name']][$i][4] = round(floatval($ya_report['pageDepth'][$i]),2);
               $finish_array[$ya_report['name']][$i][5] = $this->printSeconds($ya_report['avgVisitDurationSeconds'][$i]);
            }
        }
        \Yii::trace(print_r($graph_array,true));
        $total_array = array();
        // формируем общую основную таблиц
        for($i=0; $i < count($monthes); $i++) {
             $total_array[$i]['Визиты'] = $start_array['totals'][0][$i];
             $total_array[$i]['Посетители'] = $start_array['totals'][1][$i];
             $total_array[$i]['Отказы'] = round(floatval($start_array['totals'][2][$i]),2);
             $total_array[$i]['Глубина просмотра'] = round(floatval($start_array['totals'][3][$i]),2);
             $total_array[$i]['Время на сайте'] = $this->printSeconds($start_array['totals'][4][$i]);
        }
        $params['total_array'] = $total_array;
        \Yii::trace('total_array');
        \Yii::trace(print_r($total_array,true));
        //$html - отрендеренный кусок хтмл кода
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/LinesAPhead.php', $params);

        $params['title'] = 'Посещаемость сайта в период c '.$date_start.' по '.$date_end .' + "суммарно по всем источникам"';
        $params['header'] = $header_total;
        $params['table_th'] = ['Визиты','Посетители','Отказы','Глубина просмотра','Время на сайте'];

        $html .= \Yii::$app->view->renderFile('@app/views/report/worksdone/yaMetrikaTotal.php', $params);

        // рисуем первый график
        $script_for_one_graph = $script_for_total_graph = '';
        $script =   '<script type="text/javascript">function initLinesYAP(){ var dataObj;';
        $canvas = '';
        $params['order']++;
        // рисуем общий график для общей таблицы
        $params['data'] = $graph_array;
        $script_for_total_graph .= \Yii::$app->view->renderFile('@app/views/report/worksdone/LinesYAP.php', $params);
        $canvas .= '<div class="col-xs-12" style="margin-bottom:30px;"><canvas id="yaColeb'.$params["order"].'" class="graph"></canvas></div>';

        //$html .= $canvas.$script;
        $html .= $canvas;
        // рисуем отдельные показатели
        $params['header'] = $header_array;
        \Yii::trace(print_r($graph_array['Внутренние переходы'],true));
        foreach ($finish_array as $key => $value) {
             // графики по отдельным показателям
             $script_for_one_graph .= \Yii::$app->view->renderFile('@app/views/report/worksdone/LinesOneYAP.php', array('data' => $graph_array[$key], 'order' => $params["order"]++));
             $html .= '<div class="col-xs-12"><h3 class="h3" style="margin-bottom:0;">'.$key.'</h3></div><div class="col-xs-12"><h5 class="h5" style="margin:0;">Источник: '.$key.'</h5></div><div class="col-xs-12"><canvas id="yaColeb'.$params["order"].'" class="graph"></canvas></div>';
             // таблицы по отдельным показателям
             $params['finish_data'] = $finish_array;
             $html .= \Yii::$app->view->renderFile('@app/views/report/worksdone/yaMetrika.php', array('key_name' => $key, 'table_value' => $value, 'header' => $header_array));
        }
        $script = $script.$script_for_total_graph.$script_for_one_graph.'}</script>';
        // закрываем необходимые теги
        $html .= $script;
        $html .= \Yii::$app->view->renderFile('@app/views/report/worksdone/LinesAPfooter.php', $params);



        return $html;
    }

     public function getPeriod($start,$end){
          \Yii::trace(date("n",strtotime($start)));
          \Yii::trace(date("n",strtotime($end)));
          $monthes = array(1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь');

          $months = array();
          $m2 = date("n",strtotime($end));
          $m1 = date("n",strtotime($start));
          if($m1 > $m2) $m2 += 12;
          for ($i=$m1; $i <= $m2; $i++) {
               if($i>12)
                    $months[] = $monthes[($i%12)];
               else
                    $months[] = $monthes[$i];
          }
          \Yii::trace(print_r($months,true));
          return $months;
     }

     public function getReportPartSettings($id){
          // получаем настройки для текущей части отчета
          $currentCriteries = \app\models\ReportPartReport::find()->where(['id' => $id])->one();
          // декодируем данные из настроек части отчета
          $settings = json_decode($currentCriteries->settings,true);
          return $settings;
     }

}
