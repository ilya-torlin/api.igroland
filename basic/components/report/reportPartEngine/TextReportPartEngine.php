<?php

namespace app\components\report\reportPartEngine;

// класс генерации текстовых полей части отчета
class TextReportPartEngine implements \app\components\report\ReportPartEngineInterface
{
     public function getAlert(){
      return false;
    }
     public function render($data){
         // заполняем часть отчета - таблицы по позициям
        $report_part_id = $data;
        // задаем порядок
        $arr = array();
        $part = $data['thispart'];
        if ($part['show']) {
             $set = json_decode($part['settings'], true);
             foreach ($set as $s) {
                  if($s['type'] === 'name')
                    $name = $s['value'];
             }
        }
        //\Yii::trace(print_r($data,true));
        $params['name'] = $name;
        $params['order'] = $data['order'];
        // получаем настройки части отчета
        $params['text'] = $this->getReportPartSettings($part['id']);  // в json формате
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/textarea.php', $params);
        return $html;
     }

     public function getReportPartSettings($id){
          // получаем настройки для текущей части отчета
          $currentCriteries = \app\models\ReportPartReport::find()->where(['id' => $id])->one();
          $settings = json_decode($currentCriteries->settings,true);
          return $settings;
     }
}
